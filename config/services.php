<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cloudflare Workers Integration
    |--------------------------------------------------------------------------
    | Used for offloading domain checks to Cloudflare Workers.
    | The webhook_secret authenticates incoming requests from Workers.
    */
    'cloudflare' => [
        'webhook_secret' => env('CLOUDFLARE_WEBHOOK_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Stripe Integration
    |--------------------------------------------------------------------------
    | Configuration for Stripe payment processing.
    | Use test keys for development and live keys for production.
    */
    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'statement_descriptor' => env('STRIPE_STATEMENT_DESCRIPTOR', 'Domain Monitor'),
    ],

    /*
    |--------------------------------------------------------------------------
    | WHOIS Integration
    |--------------------------------------------------------------------------
    | Configuration for domain expiration checking via WHOIS.
    | Optional: Set WHOIS_API_KEY and WHOIS_API_URL if using a WHOIS API service.
    | If not set, the system will attempt to use command-line 'whois' tool.
    */
    'whois' => [
        'api_key' => env('WHOIS_API_KEY'),
        'api_url' => env('WHOIS_API_URL', 'https://www.whoisxmlapi.com/whoisserver/WhoisService'),
    ],

];
