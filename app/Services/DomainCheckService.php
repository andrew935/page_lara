<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class DomainCheckService
{
    /**
     * Check a domain for HTTP reachability and SSL validity.
     */
    public function check(string $domain): array
    {
        $result = [
            'status' => 'error',
            'ssl_valid' => null,
            'error' => null,
            'checked_at' => Carbon::now(),
        ];

        $timeout = (int) config('domain.timeout', 5);
        $url = "https://{$domain}";
        $strictError = null;

        try {
            $response = Http::timeout($timeout)->withOptions([
                'verify' => true,
            ])->get($url);

            $isUp = $response->successful() || $response->redirect();
            $result['status'] = $isUp ? 'ok' : 'down';
        } catch (\Throwable $e) {
            $result['status'] = 'down';
            $strictError = $e->getMessage();
            $result['error'] = $strictError;

            // Retry with relaxed SSL to determine reachability
            if (str_contains($strictError, 'SSL certificate')) {
                try {
                    $response = Http::timeout($timeout)->withOptions([
                        'verify' => false,
                    ])->get($url);

                    if ($response->successful() || $response->redirect()) {
                        $result['status'] = 'ok';
                        // Keep the original SSL error to indicate chain issues
                        $result['error'] = 'Reachable but SSL chain issue: ' . $strictError;
                    }
                } catch (\Throwable $e2) {
                    // Keep original error if relaxed attempt also fails
                    $result['error'] = $strictError;
                }
            }
        }

        // SSL validity check
        $sslValid = $this->checkSsl($domain, $timeout);
        $result['ssl_valid'] = $sslValid['valid'];
        if (!$result['error'] && $sslValid['error']) {
            $result['error'] = $sslValid['error'];
        }

        return $result;
    }

    /**
     * Validate SSL certificate.
     */
    protected function checkSsl(string $domain, int $timeout): array
    {
        $context = stream_context_create([
            'ssl' => [
                'capture_peer_cert' => true,
                'verify_peer' => true,
                'verify_peer_name' => true,
                'allow_self_signed' => false,
            ],
        ]);

        $client = @stream_socket_client(
            "ssl://{$domain}:443",
            $errno,
            $errstr,
            $timeout,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!$client) {
            return ['valid' => false, 'error' => $errstr ?: 'SSL connection failed'];
        }

        $params = stream_context_get_params($client);
        if (empty($params['options']['ssl']['peer_certificate'])) {
            return ['valid' => false, 'error' => 'No peer certificate'];
        }

        $cert = $params['options']['ssl']['peer_certificate'];
        $parsed = openssl_x509_parse($cert);
        if (!$parsed) {
            return ['valid' => false, 'error' => 'Unable to parse certificate'];
        }

        // Hostname check (fallback if openssl_x509_check_host is unavailable)
        $hostValid = false;
        if (function_exists('openssl_x509_check_host')) {
            $hostValid = @openssl_x509_check_host($cert, $domain, 0, OPENSSL_X509_CHECK_FLAG_NO_PARTIAL_WILDCARDS);
        } else {
            $hostValid = $this->hostMatches($domain, $parsed);
        }

        if (!$hostValid) {
            return ['valid' => false, 'error' => 'Certificate does not match host'];
        }

        // Expiry check
        $validTo = $parsed['validTo_time_t'] ?? null;
        if (!$validTo || $validTo < time()) {
            return ['valid' => false, 'error' => 'Certificate expired'];
        }

        return ['valid' => true, 'error' => null];
    }

    /**
     * Fallback host match if openssl_x509_check_host is unavailable.
     */
    protected function hostMatches(string $domain, array $parsed): bool
    {
        // Subject Alternative Names
        $sans = [];
        if (!empty($parsed['extensions']['subjectAltName'])) {
            $parts = explode(',', $parsed['extensions']['subjectAltName']);
            foreach ($parts as $part) {
                $part = trim($part);
                if (stripos($part, 'DNS:') === 0) {
                    $sans[] = substr($part, 4);
                }
            }
        }

        // Common Name
        if (!empty($parsed['subject']['CN'])) {
            $sans[] = $parsed['subject']['CN'];
        }

        foreach ($sans as $name) {
            // Wildcard match
            if (str_starts_with($name, '*.')) {
                $suffix = substr($name, 1); // keep the dot
                if (str_ends_with($domain, $suffix)) {
                    return true;
                }
            } else {
                if (strcasecmp($domain, $name) === 0) {
                    return true;
                }
            }
        }

        return false;
    }
}

