<?php
declare(strict_types=1);

/**
 * Users example module manifest (preferred format).
 * This module is shared across environments and demonstrates auth and admin routes.
 *
 * @return array<string, mixed>
 */
return [
    'name' => 'Users',
    'version' => '0.1.0',
    'enabled' => true,
    'env' => 'shared',
    'routes' => __DIR__ . '/routes.php',
    'export' => [
        'Controllers',
        'Models',
        'Views',
        'routes.php',
        'assets',
        'Database',
        'Middleware',
        'Services',
    ],
];
