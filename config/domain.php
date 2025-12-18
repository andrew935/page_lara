<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Domain Check Mode
    |--------------------------------------------------------------------------
    |
    | This option controls how domain checks are performed:
    |
    | - "server": Use Laravel scheduler + queue workers (free, slower)
    |   Checks 10 domains per minute, all domains checked over 50 minutes
    |
    | - "cloudflare": Use Cloudflare Workers + Queues (paid $5/month, faster)
    |   Checks all 500 domains in 1-2 minutes every 20 minutes
    |
    */
    'check_mode' => env('DOMAIN_CHECK_MODE', 'server'),

    /*
    |--------------------------------------------------------------------------
    | Check Timeout
    |--------------------------------------------------------------------------
    |
    | The timeout (in seconds) for HTTP requests when checking domains.
    |
    */
    'timeout' => env('DOMAIN_CHECK_TIMEOUT', 5),

    /*
    |--------------------------------------------------------------------------
    | Mode Information
    |--------------------------------------------------------------------------
    |
    | Detailed information about each checking mode.
    |
    */
    'modes' => [
        'server' => [
            'name' => 'Server-Based Checking',
            'cost' => 'Free',
            'speed' => '10 domains/minute',
            'total_time' => '~50 minutes for 500 domains',
            'requires' => 'Docker scheduler service running',
            'pros' => [
                'No additional costs',
                'Full control over checking logic',
                'Works on free tier',
            ],
            'cons' => [
                'Slower checking speed',
                'Uses server resources (CPU/memory)',
                'May timeout with 1000+ domains',
            ],
        ],
        'cloudflare' => [
            'name' => 'Cloudflare Workers',
            'cost' => '$5/month',
            'speed' => '50 concurrent workers',
            'total_time' => '~2 minutes for 500 domains',
            'requires' => 'Cloudflare Workers Paid plan + deployed worker',
            'pros' => [
                'Very fast checking (50x faster)',
                'No server resource usage',
                'Global edge network',
                'Scales to 10,000+ domains easily',
            ],
            'cons' => [
                '$5/month cost',
                'Requires Cloudflare account setup',
                'Less control over checking logic',
            ],
        ],
    ],
];
