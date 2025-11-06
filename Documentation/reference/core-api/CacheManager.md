# CacheManager

Namespace: `Ishmael\Core\Cache`  
Source: `IshmaelPHP-Core\app\Core\Cache\CacheManager.php`

### Public methods
- `clearAll(): void` — Clear all cache entries for known namespaces when supported by the underlying store.
- `clearNamespace(string $namespace): void`
- `clearTag(string $tag, ?string $namespace = NULL): void`
- `forget(string $key, string $namespace = 'default'): void`
- `get(string $key, mixed $default = NULL, string $namespace = 'default'): mixed`
- `getStats(): array` — Return optional stats for the current store (counts only when cheap to compute).
- `has(string $key, string $namespace = 'default'): bool`
- `instance(): Ishmael\Core\Cache\CacheManager`
- `remember(string $key, callable $callback, ?int $ttlSeconds = NULL, string $namespace = 'default', array $tags = array (
)): mixed`
- `set(string $key, mixed $value, ?int $ttlSeconds = NULL, string $namespace = 'default', array $tags = array (
)): void`
- `store(): Ishmael\Core\Cache\CacheStore`
