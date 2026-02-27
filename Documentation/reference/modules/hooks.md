# Module Lifecycle Hooks

This page defines the `hooks` and `listeners` sections for module manifests.

What are hooks?

- boot: runs early in the module lifecycle (after DI is ready, before routes are finalised)
- shutdown: runs late during application termination
- listeners: mapping of `eventName => handler reference` to subscribe to events. These are automatically registered to the `Dispatcher` during application boot.

Handler reference formats

- "FQCN@method" string, e.g., "Modules\\Blog\\Bootstrap@boot"
- [FQCN, "method"] array, e.g., [Modules\\Blog\\Bootstrap::class, "boot"]
- FQCN string for invokable classes, e.g., Modules\\Blog\\Listeners\\InvalidateCache::class
- Absolute path to a PHP file returning a Closure (advanced)

Example manifest (module.php)

```php
<?php
declare(strict_types=1);

use Psr\Log\LoggerInterface;

/**
 * Blog module with hooks.
 * @return array<string, mixed>
 */
return [
    'name' => 'Blog',
    'version' => '1.1.0',
    'env' => 'shared',
    'routes' => __DIR__ . '/routes.php',
    'listeners' => [
        'user.registered' => [Modules\Blog\Listeners\SendWelcomeEmail::class, 'handle'],
        'posts.cleared' => Modules\Blog\Listeners\InvalidateCache::class
    ]
];

namespace Modules\\Blog;

final class Bootstrap
{
    /**
     * Module boot hook.
     * @param LoggerInterface $logger Logger (example dependency)
     * @return void
     */
    public static function boot(LoggerInterface $logger): void
    {
        $logger->info('Blog module booted');
    }

    /**
     * Module shutdown hook.
     * @return void
     */
    public static function shutdown(): void
    {
        // Flush metrics, close resources, etc.
    }
}

namespace Modules\\Blog\\Listeners;

final class SendWelcomeEmail
{
    /**
     * Example event handler.
     * @param array<string, mixed> $payload Event payload
     * @return void
     */
    public function handle(array $payload): void
    {
        // send email …
    }
}

final class InvalidateCache
{
    /**
     * Invokable example handler.
     * @param array<string, mixed> $payload Event payload
     * @return void
     */
    public function __invoke(array $payload): void
    {
        // clear cache …
    }
}
```

Example manifest (module.json)

```json
{
  "name": "Payments",
  "version": "1.2.0",
  "env": "production",
  "routes": "<MODULE_DIR>/routes.php",
  "listeners": {
    "payments.captured": ["Modules\\Payments\\Listeners\\NotifyAccounting", "handle"]
  }
}
```

Constraints and guidance

- Hooks and listeners are optional.
- Keep handlers small and idempotent; avoid heavy work in boot/shutdown.
- Use PascalCase for classes, camelCase for methods. Include PHPDoc in handlers.
- Prefer keeping handler classes within the module boundary.

Status: Active.
