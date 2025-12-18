<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to authenticate Cloudflare Worker webhook requests.
 * 
 * Expects Bearer token in Authorization header that matches CLOUDFLARE_WEBHOOK_SECRET env var.
 */
class CloudflareWebhookAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('services.cloudflare.webhook_secret');

        if (!$secret) {
            return response()->json(['error' => 'Webhook secret not configured'], 500);
        }

        $token = $request->bearerToken();

        if (!$token || !hash_equals($secret, $token)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}

