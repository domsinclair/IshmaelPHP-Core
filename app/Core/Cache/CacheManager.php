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

    /**
     * Clear all cache entries for known namespaces when supported by the underlying store.
     * For file stores, this deletes all cache files under the base directory.
     * For database stores, it truncates the cache table.
     */
    public function clearAll(): void
    {
        $store = $this->store;
        if ($store instanceof FileCacheStore) {
        // Best effort: remove all *.cache.json under storage/cache
            $ref = new \ReflectionClass($store);
            $prop = $ref->getProperty('baseDir');
            $prop->setAccessible(true);
            $baseDir = (string)$prop->getValue($store);
            if (is_dir($baseDir)) {
                foreach (glob($baseDir . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR) ?: [] as $nsDir) {
                    foreach (glob($nsDir . DIRECTORY_SEPARATOR . '*.cache.json') ?: [] as $file) {
                        @unlink($file);
                    }
                }
            }
            return;
        }
        if ($store instanceof DatabaseCacheStore) {
            $ref = new \ReflectionClass($store);
            $pdoProp = $ref->getProperty('pdo');
            $pdoProp->setAccessible(true);
/** @var \PDO $pdo */
            $pdo = $pdoProp->getValue($store);
            $tableProp = $ref->getProperty('table');
            $tableProp->setAccessible(true);
            $table = (string)$tableProp->getValue($store);
            $pdo->exec("DELETE FROM {$table}");
            return;
        }
        // Fallback: attempt to purge expired in default namespace
        $store->purgeExpired();
    }

    /**
     * Return optional stats for the current store (counts only when cheap to compute).
     * @return array<string,int|string>
     */
    public function getStats(): array
    {
        $store = $this->store;
        if ($store instanceof FileCacheStore) {
            $ref = new \ReflectionClass($store);
            $prop = $ref->getProperty('baseDir');
            $prop->setAccessible(true);
            $baseDir = (string)$prop->getValue($store);
            $count = 0;
            if (is_dir($baseDir)) {
                foreach (glob($baseDir . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR) ?: [] as $nsDir) {
                    $count += count(glob($nsDir . DIRECTORY_SEPARATOR . '*.cache.json') ?: []);
                }
            }
            return ['driver' => 'file', 'items' => $count];
        }
        if ($store instanceof DatabaseCacheStore) {
            $ref = new \ReflectionClass($store);
            $pdoProp = $ref->getProperty('pdo');
            $pdoProp->setAccessible(true);
/** @var \PDO $pdo */
            $pdo = $pdoProp->getValue($store);
            $tableProp = $ref->getProperty('table');
            $tableProp->setAccessible(true);
            $table = (string)$tableProp->getValue($store);
            $res = $pdo->query("SELECT COUNT(*) as c FROM {$table}");
            $row = $res ? $res->fetch(\PDO::FETCH_ASSOC) : ['c' => 0];
            return ['driver' => 'database', 'items' => (int)($row['c'] ?? 0)];
        }
        return ['driver' => 'array', 'items' => 0];
    }

    public static function instance(): CacheManager
    {
        if (self::$instance) {
            return self::$instance;
        }

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
    {
        return $this->store->get($key, $default, $namespace);
    }

    public function set(string $key, mixed $value, ?int $ttlSeconds = null, string $namespace = 'default', array $tags = []): void
    {
        $this->store->set($key, $value, $ttlSeconds ?? $this->defaultTtl, $namespace, $tags);
    }

    public function has(string $key, string $namespace = 'default'): bool
    {
        return $this->store->has($key, $namespace);
    }

    public function forget(string $key, string $namespace = 'default'): void
    {
        $this->store->delete($key, $namespace);
    }

    public function clearNamespace(string $namespace): void
    {
        $this->store->clearNamespace($namespace);
    }

    public function clearTag(string $tag, ?string $namespace = null): void
    {
        $this->store->clearTag($tag, $namespace);
    }

    public function remember(string $key, callable $callback, ?int $ttlSeconds = null, string $namespace = 'default', array $tags = []): mixed
    {
        return $this->store->remember($key, $callback, $ttlSeconds ?? $this->defaultTtl, $namespace, $tags);
    }

    public function store(): CacheStore
    {
        return $this->store;
    }
}
