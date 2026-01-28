<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DomainExpirationService
{
    /**
     * Check domain registration expiration date via WHOIS.
     * 
     * @param string $domain The domain name to check
     * @return array{expires_at: Carbon|null, error: string|null}
     */
    public function checkExpiration(string $domain): array
    {
        try {
            // Use a WHOIS API service
            // Option 1: Use a free WHOIS API (e.g., whoisxmlapi.com, whois.com)
            // Option 2: Use command-line whois if available
            // Option 3: Use a PHP WHOIS library
            
            // For now, we'll use a simple approach with whois command or API
            $expirationDate = $this->queryWhois($domain);
            
            return [
                'expires_at' => $expirationDate,
                'error' => $expirationDate ? null : 'Unable to retrieve expiration date',
            ];
        } catch (\Throwable $e) {
            Log::warning("Failed to check domain expiration for {$domain}: " . $e->getMessage());
            
            return [
                'expires_at' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Query WHOIS data for domain expiration.
     * 
     * This method tries multiple approaches:
     * 1. Command-line whois (if available)
     * 2. WHOIS API service (if configured)
     * 3. Parse from common WHOIS patterns
     */
    protected function queryWhois(string $domain): ?Carbon
    {
        // Try command-line whois first (most reliable)
        if ($this->hasWhoisCommand()) {
            $expirationDate = $this->queryWhoisCommand($domain);
            if ($expirationDate) {
                return $expirationDate;
            }
        }

        // Try WHOIS API service if configured
        $apiKey = config('services.whois.api_key');
        if ($apiKey) {
            $expirationDate = $this->queryWhoisApi($domain, $apiKey);
            if ($expirationDate) {
                return $expirationDate;
            }
        }

        // Fallback: Try direct HTTP WHOIS query (limited support)
        return $this->queryWhoisHttp($domain);
    }

    /**
     * Check if whois command is available.
     */
    protected function hasWhoisCommand(): bool
    {
        $command = 'which whois';
        if (PHP_OS_FAMILY === 'Windows') {
            $command = 'where whois';
        }
        
        $result = shell_exec($command . ' 2>&1');
        return !empty($result) && strpos($result, 'whois') !== false;
    }

    /**
     * Query WHOIS using command-line tool.
     */
    protected function queryWhoisCommand(string $domain): ?Carbon
    {
        try {
            $command = escapeshellcmd("whois {$domain}");
            $output = shell_exec($command . ' 2>&1');
            
            if (empty($output)) {
                return null;
            }

            return $this->parseWhoisOutput($output);
        } catch (\Throwable $e) {
            Log::debug("Command-line whois failed for {$domain}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Query WHOIS using API service.
     */
    protected function queryWhoisApi(string $domain, string $apiKey): ?Carbon
    {
        try {
            // Example: Using whoisxmlapi.com (you can use any WHOIS API service)
            $apiUrl = config('services.whois.api_url', 'https://www.whoisxmlapi.com/whoisserver/WhoisService');
            
            $response = Http::timeout(10)->get($apiUrl, [
                'apiKey' => $apiKey,
                'domainName' => $domain,
                'outputFormat' => 'JSON',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // Parse expiration date from API response
                // Format varies by API provider - supports multiple API response formats
                $expirationDate = $data['WhoisRecord']['expiresDate'] 
                    ?? $data['WhoisRecord']['registryData']['expiresDate']
                    ?? $data['expiresDate'] 
                    ?? $data['registryData']['expiresDate']
                    ?? $data['expiryDate']
                    ?? $data['expirationDate']
                    ?? $data['domain']['expiresDate']
                    ?? null;

                if ($expirationDate) {
                    try {
                        return Carbon::parse($expirationDate);
                    } catch (\Throwable $e) {
                        Log::debug("Failed to parse expiration date from API: {$expirationDate}");
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::debug("WHOIS API query failed for {$domain}: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Query WHOIS via HTTP (fallback method).
     */
    protected function queryWhoisHttp(string $domain): ?Carbon
    {
        try {
            // Some WHOIS servers allow HTTP queries
            // This is a fallback and may not work for all domains
            $tld = $this->extractTld($domain);
            $whoisServer = $this->getWhoisServer($tld);
            
            if (!$whoisServer) {
                return null;
            }

            // Try querying via HTTP (limited support)
            // Note: Most WHOIS servers don't support HTTP, so this may fail
            return null; // Disabled by default as most servers require TCP
            
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Parse WHOIS output to extract expiration date.
     * 
     * Supports various WHOIS formats including:
     * - .com, .net, .org (Verisign format)
     * - .info (Afilias format)
     * - .top, .xyz (new gTLD formats)
     * - Other common TLD formats
     */
    protected function parseWhoisOutput(string $output): ?Carbon
    {
        // Common patterns for expiration dates in WHOIS output
        // Ordered by most common first
        $patterns = [
            // Standard formats
            '/Registry Expiry Date:\s*([^\n]+)/i',
            '/Registrar Registration Expiration Date:\s*([^\n]+)/i',
            '/Expiry Date:\s*([^\n]+)/i',
            '/Expiration Date:\s*([^\n]+)/i',
            '/Expires:\s*([^\n]+)/i',
            '/Expires On:\s*([^\n]+)/i',
            '/expires:\s*([^\n]+)/i',
            '/Expiration:\s*([^\n]+)/i',
            
            // Alternative formats (used by some registrars/TLDs)
            '/paid-till:\s*([^\n]+)/i',
            '/paid until:\s*([^\n]+)/i',
            '/Registration Expiration Date:\s*([^\n]+)/i',
            '/Registry Expiration:\s*([^\n]+)/i',
            '/Expiration Time:\s*([^\n]+)/i',
            '/Expiry:\s*([^\n]+)/i',
            
            // Formats used by .top, .xyz and other new gTLDs
            '/Registry Expiry:\s*([^\n]+)/i',
            '/Domain Expiration Date:\s*([^\n]+)/i',
            '/Domain expires:\s*([^\n]+)/i',
            '/Expiry date:\s*([^\n]+)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $output, $matches)) {
                $dateString = trim($matches[1]);
                
                // Clean up common date formats
                $dateString = preg_replace('/\s+\(.*?\)/', '', $dateString); // Remove timezone in parentheses
                $dateString = trim($dateString);
                
                try {
                    $date = Carbon::parse($dateString);
                    return $date;
                } catch (\Throwable $e) {
                    // Try next pattern
                    continue;
                }
            }
        }

        return null;
    }

    /**
     * Extract TLD from domain.
     * 
     * Handles both simple TLDs (.com) and multi-part TLDs (.co.uk).
     */
    protected function extractTld(string $domain): string
    {
        $parts = explode('.', $domain);
        
        // Handle multi-part TLDs (e.g., .co.uk, .com.au)
        $multiPartTlds = ['co', 'com', 'net', 'org', 'gov', 'edu', 'ac', 'sch'];
        
        if (count($parts) >= 3) {
            $secondLast = $parts[count($parts) - 2];
            $last = end($parts);
            
            // Check if it's a known multi-part TLD
            if (in_array(strtolower($secondLast), $multiPartTlds)) {
                return $secondLast . '.' . $last;
            }
        }
        
        return end($parts);
    }

    /**
     * Get WHOIS server for a TLD.
     * 
     * Note: Command-line 'whois' tool automatically handles most TLDs,
     * so this list is mainly for reference and fallback methods.
     */
    protected function getWhoisServer(string $tld): ?string
    {
        // Common WHOIS servers (you can expand this list)
        $servers = [
            'com' => 'whois.verisign-grs.com',
            'net' => 'whois.verisign-grs.com',
            'org' => 'whois.pir.org',
            'info' => 'whois.afilias.net',
            'biz' => 'whois.neulevel.biz',
            'top' => 'whois.nic.top',
            'xyz' => 'whois.nic.xyz',
            'us' => 'whois.nic.us',
            'uk' => 'whois.nic.uk',
            'de' => 'whois.denic.de',
            'fr' => 'whois.afnic.fr',
            'nl' => 'whois.domain-registry.nl',
            'au' => 'whois.aunic.net',
            'ca' => 'whois.cira.ca',
            'io' => 'whois.nic.io',
            'co' => 'whois.nic.co',
            'me' => 'whois.nic.me',
            'tv' => 'whois.tv',
            'cc' => 'whois.nic.cc',
            'online' => 'whois.nic.online',
            'site' => 'whois.nic.site',
            'store' => 'whois.nic.store',
        ];

        return $servers[strtolower($tld)] ?? null;
    }

    /**
     * Check if domain is expiring soon.
     */
    public function isExpiringSoon(?Carbon $expiresAt, int $daysThreshold = 30): bool
    {
        if (!$expiresAt) {
            return false;
        }

        return $expiresAt->isFuture() && $expiresAt->lte(now()->addDays($daysThreshold));
    }

    /**
     * Get days until expiration.
     */
    public function daysUntilExpiration(?Carbon $expiresAt): ?int
    {
        if (!$expiresAt) {
            return null;
        }

        if ($expiresAt->isPast()) {
            return 0; // Already expired
        }

        return now()->diffInDays($expiresAt, false);
    }
}
