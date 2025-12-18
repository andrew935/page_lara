# Domain Checker - Cloudflare Worker

This Cloudflare Worker offloads domain checking from your Laravel server, handling 500+ domains efficiently with automatic retries and parallel processing.

## Cost Estimate

| Resource | Free Tier | 500 domains @ 10min | Monthly Cost |
|----------|-----------|---------------------|--------------|
| Workers | 100k req/day | ~8.6k/day | **$0** |
| Queues | 1M ops/month | ~4.3M ops | **~$1.32** |
| **Total** | — | — | **~$1.50/month** |

## Prerequisites

1. **Cloudflare Account** (free tier works)
2. **Node.js 18+** installed
3. **Wrangler CLI** installed globally

## Setup Instructions

### Step 1: Install Dependencies

```bash
cd cloudflare
npm install
```

### Step 2: Login to Cloudflare

```bash
npx wrangler login
```

This will open a browser for you to authenticate.

### Step 3: Create the Queue

```bash
# Create the main queue
npx wrangler queues create domain-check-queue

# Create the dead letter queue (for failed messages)
npx wrangler queues create domain-check-dlq
```

### Step 4: Generate a Webhook Secret

Generate a secure random string for authentication:

```bash
# On Linux/Mac
openssl rand -hex 32

# Or use any password generator to create a 64-character string
```

**Save this secret** - you'll need it for both Laravel and Cloudflare.

### Step 5: Set Cloudflare Secrets

```bash
# Set your Laravel app URL
npx wrangler secret put LARAVEL_API_URL
# Enter: https://tech-robot-automation.com

# Set the webhook secret (same as you'll put in Laravel .env)
npx wrangler secret put WEBHOOK_SECRET
# Enter: (paste the secret from Step 4)
```

### Step 6: Configure Laravel

Add to your Laravel `.env` file (or `docker/env.docker`):

```env
CLOUDFLARE_WEBHOOK_SECRET=your-secret-from-step-4
```

Then restart Laravel:

```bash
# If using Docker
docker compose restart app
```

### Step 7: Deploy the Worker

```bash
npm run deploy
# or
npx wrangler deploy
```

### Step 8: Verify Deployment

Check the worker is running:

```bash
# View real-time logs
npx wrangler tail

# Or check health endpoint
curl https://domain-checker.YOUR-SUBDOMAIN.workers.dev/health
```

## Testing

### Test Single Domain Check

```bash
curl -X POST https://domain-checker.YOUR-SUBDOMAIN.workers.dev/check \
  -H "Authorization: Bearer YOUR_WEBHOOK_SECRET" \
  -H "Content-Type: application/json" \
  -d '{"domain": "google.com"}'
```

### Manually Trigger Check Cycle

```bash
curl -X POST https://domain-checker.YOUR-SUBDOMAIN.workers.dev/trigger \
  -H "Authorization: Bearer YOUR_WEBHOOK_SECRET"
```

### View Logs

```bash
npx wrangler tail
```

## Disable Server-Side Scheduler

Once the Cloudflare Worker is running successfully, stop the Laravel scheduler to avoid duplicate checks:

```bash
# SSH into your server
cd ~/page_lara

# Stop only the scheduler (keep other services)
docker compose stop scheduler

# Verify it's stopped
docker compose ps
```

## Rollback

If issues occur, restart the server scheduler:

```bash
docker compose start scheduler
```

## Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                     CLOUDFLARE EDGE                              │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────────────┐  │
│  │ Cron Trigger│───▶│  Scheduler  │───▶│  Queue (domain-     │  │
│  │ (every 10m) │    │   Worker    │    │  check-queue)       │  │
│  └─────────────┘    └─────────────┘    └──────────┬──────────┘  │
│                            │                      │              │
│                            │ GET /api/cf/         │              │
│                            │ domains/due          │              │
│                            ▼                      ▼              │
│                     ┌─────────────┐    ┌─────────────────────┐  │
│                     │   Laravel   │◀───│  Consumer Worker    │  │
│                     │     API     │    │  (checks domains)   │  │
│                     └─────────────┘    └─────────────────────┘  │
│                            ▲                      │              │
│                            │ POST /api/cf/        │              │
│                            │ domains/results      │              │
│                            └──────────────────────┘              │
└─────────────────────────────────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                      YOUR SERVER                                 │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────────────┐  │
│  │   Laravel   │───▶│   MySQL     │    │  Queue (Telegram,   │  │
│  │   App       │    │   Database  │    │  notifications)     │  │
│  └─────────────┘    └─────────────┘    └─────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
```

## Troubleshooting

### "Failed to fetch domains" in logs

- Check `LARAVEL_API_URL` is correct
- Verify `WEBHOOK_SECRET` matches Laravel's `CLOUDFLARE_WEBHOOK_SECRET`
- Ensure Laravel API is accessible from the internet

### Queue not processing

```bash
# Check queue status
npx wrangler queues list

# Check for stuck messages
npx wrangler queues consumer domain-check-queue --dry-run
```

### Domains not updating in Laravel

- Check Laravel logs: `docker compose logs app -f`
- Verify the `/api/cf/domains/results` endpoint is accessible
- Check for any 401/403 errors (auth issues)

## Monitoring

### Cloudflare Dashboard

1. Go to [Cloudflare Dashboard](https://dash.cloudflare.com)
2. Navigate to **Workers & Pages** → **domain-checker**
3. View **Metrics** for request counts, errors, CPU time
4. Check **Queues** for pending/processed messages

### Real-time Logs

```bash
npx wrangler tail
```

## Updating the Worker

After making changes to `src/index.js`:

```bash
npm run deploy
```

Changes are live within seconds.

## Scaling

The current configuration handles 500+ domains easily. To scale further:

1. **Increase concurrency** in `wrangler.toml`:
   ```toml
   max_concurrency = 20  # or higher
   ```

2. **Increase batch size**:
   ```toml
   max_batch_size = 20  # process 20 domains per worker invocation
   ```

3. **Reduce check interval** (edit cron in `wrangler.toml`):
   ```toml
   crons = ["*/5 * * * *"]  # every 5 minutes
   ```

