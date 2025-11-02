<?php
    declare(strict_types=1);

    /**
     * -------------------------------------------------------------
     * Logging Configuration
     * -------------------------------------------------------------
     * Controls how Ishmael writes logs.
     * By default, logs go to storage/logs/ishmael.log
     * with JSON formatting for structured logging.
     */

    return [
        'default' => env('LOG_CHANNEL', 'stack'),

        'channels' => [
            'stack' => [
                'driver' => 'stack',
                'channels' => ['single'],
            ],

            'single' => [
                'driver' => 'single',
                'path'   => base_path('storage/logs/ishmael.log'),
                'level'  => env('LOG_LEVEL', 'debug'),
                'format' => 'json',
            ],

            'daily' => [
                'driver' => 'daily',
                'path'   => base_path('storage/logs/ishmael.log'),
                'days'   => 7,
                'level'  => env('LOG_LEVEL', 'info'),
            ],
        ],
    ];
