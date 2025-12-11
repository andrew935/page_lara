<?php

return [
    // Timeout (seconds) for HTTP/SSL checks
    'timeout' => env('DOMAIN_CHECK_TIMEOUT', 5),

    // Max domains per scheduled batch
    'schedule_batch' => env('DOMAIN_CHECK_SCHEDULE_BATCH', 50),

    // Source API for ingestion
    'source_url' => env('DOMAIN_SOURCE_URL', 'Add the feed url here'),
];

