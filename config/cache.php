<?php
return [
    // driver: file|array|sqlite (alias for database)
    'driver' => env('CACHE_DRIVER', 'file'),

    // default TTL in seconds (0 = forever)
    'default_ttl' => (int) env('CACHE_TTL', 0),

    // key prefix (used by file driver namespaces)
    'prefix' => env('CACHE_PREFIX', 'ish'),

    // file driver path
    'path' => storage_path('cache'),

    // sqlite database settings
    'sqlite' => [
        'dsn' => 'sqlite:' . storage_path('cache/cache.sqlite'),
        'table' => 'cache',
    ],
];
