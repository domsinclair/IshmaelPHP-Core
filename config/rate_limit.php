<?php
return [
    // Namespace used in CacheStore for all throttle buckets
    'namespace' => 'rate',

    // Jitter ratio between 0 and 1 to randomize reset a bit per identity to avoid thundering herd
    'jitter_ratio' => 0.2,

    // Named presets usable as ThrottleMiddleware::with(['preset' => 'name'])
    'presets' => [
        // 60 requests per minute with burst up to 60
        'default' => [
            'capacity' => 60,
            'refillTokens' => 60,
            'refillInterval' => 60,
        ],
        // Stricter: 10 requests per 10 seconds
        'strict' => [
            'capacity' => 10,
            'refillTokens' => 10,
            'refillInterval' => 10,
        ],
        // Bursty: allow 120 burst, refill 60 per minute
        'bursty' => [
            'capacity' => 120,
            'refillTokens' => 60,
            'refillInterval' => 60,
        ],
    ],
];
