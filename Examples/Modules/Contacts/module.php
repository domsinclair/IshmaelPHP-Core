<?php
declare(strict_types=1);

/**
 * Contacts example module manifest (preferred format).
 * This example is environment-shared and suitable for development and production.
 *
 * @return array<string, mixed>
 */
return [
    'name' => 'Contacts',
    'version' => '0.1.0',
    'enabled' => true,
    // Environment compatibility: development | shared | production
    'env' => 'shared',

    // Routes file returns a Closure that accepts Router
    'routes' => __DIR__ . '/routes.php',

    // Files and directories the packer should include when building bundles
    'export' => [
        'Controllers',
        'Models',
        'Views',
        'routes.php',
        'assets',
        'Database',
    ],
];
