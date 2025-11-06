<?php
declare(strict_types=1);

namespace Ishmael\Core\Cache;

use PDO;

final class CacheManager
{
    private static ?CacheManager $instance = null;

    private CacheStore $store;
    private int $defaultTtl;

    private function __construct(CacheStore $store, int $defaultTtl)
    {
        $this->store = $store;
        $this->defaultTtl = $defaultTtl;
    }

    public static function instance(): CacheManager
    {
        if (self::$instance) return self::$instance;

        // Resolve from config/env
        $driver = (string) (config('cache.driver') ?? env('CACHE_DRIVER', 'file'));
        $prefix = (string) (config('cache.prefix') ?? 'ish');
        $defaultTtl = (int) (config('cache.default_ttl') ?? 0);

        switch ($driver) {
            case 'array':
                $store = new ArrayCacheStore();
                break;
            case 'database':
            case 'sqlite':
                $dsn = (string) (config('cache.sqlite.dsn') ?? 'sqlite:' . storage_path('cache/cache.sqlite'));
                $pdo = new PDO($dsn);
                $table = (string) (config('cache.sqlite.table') ?? 'cache');
                $store = new DatabaseCacheStore($pdo, $table);
                break;
            case 'file':
            default:
                $dir = (string) (config('cache.path') ?? storage_path('cache'));
                $store = new FileCacheStore($dir, $prefix);
                break;
        }
        self::$instance = new CacheManager($store, $defaultTtl);
        return self::$instance;
    }

    // Basic helpers
    public function get(string $key, mixed $default = null, string $namespace = 'default'): mixed
    { return $this->store->get($key, $default, $namespace); }

    public function set(string $key, mixed $value, ?int $ttlSeconds = null, string $namespace = 'default', array $tags = []): void
    { $this->store->set($key, $value, $ttlSeconds ?? $this->defaultTtl, $namespace, $tags); }

    public function has(string $key, string $namespace = 'default'): bool
    { return $this->store->has($key, $namespace); }

    public function forget(string $key, string $namespace = 'default'): void
    { $this->store->delete($key, $namespace); }

    public function clearNamespace(string $namespace): void
    { $this->store->clearNamespace($namespace); }

    public function clearTag(string $tag, ?string $namespace = null): void
    { $this->store->clearTag($tag, $namespace); }

    public function remember(string $key, callable $callback, ?int $ttlSeconds = null, string $namespace = 'default', array $tags = []): mixed
    { return $this->store->remember($key, $callback, $ttlSeconds ?? $this->defaultTtl, $namespace, $tags); }

    public function store(): CacheStore
    { return $this->store; }
}
