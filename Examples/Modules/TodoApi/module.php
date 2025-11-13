<?php
declare(strict_types=1);

/**
 * TodoApi example module manifest (preferred format).
 * JSON API with throttling and ETag hints; shared across environments.
 *
 * @return array<string, mixed>
 */
return [
    'name' => 'TodoApi',
    'version' => '0.1.0',
    'enabled' => true,
    'env' => 'shared',
    'routes' => __DIR__ . '/routes.php',
    'export' => [
        'Controllers',
        'Models',
        'routes.php',
        'assets',
        'Database',
    ],
];
