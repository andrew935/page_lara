/**
 * Cloudflare Worker for Domain Checking
 * 
 * This worker handles domain availability and SSL checks, offloading the work
 * from the Laravel server. It uses Cloudflare Queues for reliable processing.
 * 
 * Architecture:
 * 1. Cron trigger (every 10 min) → Fetch domains due → Queue all domains
 * 2. Queue consumer → Check each domain → Report results to Laravel
 */

// Configuration (set via wrangler secrets)
// - LARAVEL_API_URL: Your Laravel app URL (e.g., https://tech-robot-automation.com)
// - WEBHOOK_SECRET: Bearer token for authenticating with Laravel API

/**
 * Main Worker Entry Point
 */
export default {
  /**
   * Scheduled (Cron) Handler
   * Runs every 20 minutes to fetch and queue ALL domains for checking
   */
  async scheduled(event, env, ctx) {
    console.log('Cron triggered: Fetching ALL domains due for checking...');
    
    try {
      // Fetch ALL domains that need checking from Laravel (up to 1000)
      const response = await fetch(`${env.LARAVEL_API_URL}/api/cf/domains/due?limit=1000`, {
        headers: {
          'Authorization': `Bearer ${env.WEBHOOK_SECRET}`,
          'Accept': 'application/json',
        },
      });

      if (!response.ok) {
        console.error(`Failed to fetch domains: ${response.status} ${response.statusText}`);
        return;
      }

      const data = await response.json();
      console.log(`Fetched ${data.count} domains to check`);

      if (!data.domains || data.domains.length === 0) {
        console.log('No domains due for checking');
        return;
      }

      // Queue all domains for checking (batch send for efficiency)
      const batchSize = 100;
      for (let i = 0; i < data.domains.length; i += batchSize) {
        const batch = data.domains.slice(i, i + batchSize);
        const messages = batch.map(domain => ({
          body: {
            id: domain.id,
            domain: domain.domain,
            campaign: domain.campaign,
            account_id: domain.account_id,
          },
        }));

        await env.DOMAIN_CHECK_QUEUE.sendBatch(messages);
        console.log(`Queued batch ${Math.floor(i / batchSize) + 1}: ${batch.length} domains`);
      }

      console.log(`Successfully queued ${data.domains.length} domains for checking`);
    } catch (error) {
      console.error('Error in scheduled handler:', error);
    }
  },

  /**
   * Queue Consumer Handler
   * Processes domain check jobs from the queue
   */
  async queue(batch, env, ctx) {
    console.log(`Processing batch of ${batch.messages.length} domains`);

    const results = [];

    for (const message of batch.messages) {
      const { id, domain, campaign, account_id } = message.body;
      
      try {
        console.log(`Checking domain: ${domain}`);
        
        // Perform the domain check
        const result = await checkDomain(domain);
        
        results.push({
          id,
          status: result.status,
          ssl_valid: result.ssl_valid,
          error: result.error,
          checked_at: new Date().toISOString(),
        });

        // Acknowledge successful processing
        message.ack();
      } catch (error) {
        console.error(`Error checking domain ${domain}:`, error);
        
        results.push({
          id,
          status: 'error',
          ssl_valid: null,
          error: error.message || 'Unknown error',
          checked_at: new Date().toISOString(),
        });

        // Retry failed messages (up to max retries configured in wrangler.toml)
        message.retry();
      }
    }

    // Send batch results to Laravel
    if (results.length > 0) {
      try {
        const response = await fetch(`${env.LARAVEL_API_URL}/api/cf/domains/results`, {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${env.WEBHOOK_SECRET}`,
            'Content-Type': 'application/json',
            'Accept': 'application/json',
          },
          body: JSON.stringify({ results }),
        });

        if (!response.ok) {
          console.error(`Failed to send results: ${response.status} ${response.statusText}`);
        } else {
          const data = await response.json();
          console.log(`Results sent: ${data.processed} processed, ${data.errors?.length || 0} errors`);
        }
      } catch (error) {
        console.error('Error sending results to Laravel:', error);
      }
    }
  },

  /**
   * HTTP Request Handler (for manual testing)
   */
  async fetch(request, env, ctx) {
    const url = new URL(request.url);

    // Health check endpoint
    if (url.pathname === '/health') {
      return new Response(JSON.stringify({ status: 'ok', timestamp: new Date().toISOString() }), {
        headers: { 'Content-Type': 'application/json' },
      });
    }

    // Manual trigger for testing (requires auth)
    if (url.pathname === '/trigger' && request.method === 'POST') {
      const authHeader = request.headers.get('Authorization');
      if (authHeader !== `Bearer ${env.WEBHOOK_SECRET}`) {
        return new Response('Unauthorized', { status: 401 });
      }

      // Trigger the scheduled handler manually
      ctx.waitUntil(this.scheduled({}, env, ctx));
      return new Response(JSON.stringify({ message: 'Triggered domain check' }), {
        headers: { 'Content-Type': 'application/json' },
      });
    }

    // Single domain check for testing
    if (url.pathname === '/check' && request.method === 'POST') {
      const authHeader = request.headers.get('Authorization');
      if (authHeader !== `Bearer ${env.WEBHOOK_SECRET}`) {
        return new Response('Unauthorized', { status: 401 });
      }

      const body = await request.json();
      if (!body.domain) {
        return new Response(JSON.stringify({ error: 'Missing domain parameter' }), {
          status: 400,
          headers: { 'Content-Type': 'application/json' },
        });
      }

      const result = await checkDomain(body.domain);
      return new Response(JSON.stringify(result), {
        headers: { 'Content-Type': 'application/json' },
      });
    }

    return new Response('Domain Checker Worker', { status: 200 });
  },
};

/**
 * Check a domain for HTTP reachability and SSL validity
 * Replicates the logic from Laravel's DomainCheckService
 */
async function checkDomain(domain) {
  const result = {
    domain,
    status: 'error',
    ssl_valid: null,
    error: null,
    response_time: null,
  };

  const timeout = 10000; // 10 seconds
  const url = `https://${domain}`;
  const startTime = Date.now();

  try {
    // First attempt: strict SSL verification
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), timeout);

    const response = await fetch(url, {
      method: 'HEAD', // Faster than GET
      signal: controller.signal,
      redirect: 'follow',
      headers: {
        'User-Agent': 'DomainChecker/1.0 (Cloudflare Worker)',
      },
    });

    clearTimeout(timeoutId);
    result.response_time = Date.now() - startTime;

    // Check if response is successful or redirect
    if (response.ok || (response.status >= 300 && response.status < 400)) {
      result.status = 'ok';
      result.ssl_valid = true; // SSL is valid if we got here via HTTPS
    } else {
      result.status = 'down';
      result.error = `HTTP ${response.status}: ${response.statusText}`;
    }
  } catch (error) {
    result.response_time = Date.now() - startTime;

    if (error.name === 'AbortError') {
      result.status = 'down';
      result.error = 'Connection timeout';
    } else if (error.message.includes('SSL') || error.message.includes('certificate')) {
      // SSL error - domain might still be reachable
      result.status = 'down';
      result.ssl_valid = false;
      result.error = `SSL Error: ${error.message}`;
      
      // Try HTTP fallback to check if domain exists
      try {
        const httpResponse = await fetch(`http://${domain}`, {
          method: 'HEAD',
          redirect: 'follow',
        });
        
        if (httpResponse.ok || httpResponse.status >= 300) {
          // Domain is reachable but SSL has issues
          result.status = 'ok';
          result.error = 'Reachable but SSL invalid';
        }
      } catch {
        // HTTP fallback also failed
      }
    } else if (error.message.includes('DNS') || error.message.includes('ENOTFOUND')) {
      result.status = 'down';
      result.error = 'DNS resolution failed';
    } else if (error.message.includes('ECONNREFUSED')) {
      result.status = 'down';
      result.error = 'Connection refused';
    } else {
      result.status = 'down';
      result.error = error.message || 'Unknown error';
    }
  }

  return result;
}

/**
 * Check SSL certificate validity (detailed check)
 * Note: Cloudflare Workers have limited SSL inspection capabilities.
 * The main check above handles basic SSL validation via fetch().
 */
async function checkSSL(domain) {
  // In Cloudflare Workers, we can't do deep SSL inspection like we can in PHP.
  // The fetch() call above already validates SSL by default.
  // If fetch succeeds over HTTPS, SSL is valid.
  // This function is a placeholder for any additional SSL checks needed.
  return { valid: true, error: null };
}

