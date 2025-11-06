# FileCacheStore

Namespace: `Ishmael\Core\Cache`  
Source: `IshmaelPHP-Core\app\Core\Cache\FileCacheStore.php`

### Public methods
- `__construct(string $baseDir, string $prefix = '')`
- `clearNamespace(string $namespace): void`
- `clearTag(string $tag, ?string $namespace = NULL): void`
- `delete(string $key, string $namespace = 'default'): void`
- `get(string $key, mixed $default = NULL, string $namespace = 'default'): mixed`
- `has(string $key, string $namespace = 'default'): bool`
- `purgeExpired(?string $namespace = NULL): void`
- `remember(string $key, callable $callback, ?int $ttlSeconds = NULL, string $namespace = 'default', array $tags = array (
)): mixed`
- `set(string $key, mixed $value, ?int $ttlSeconds = NULL, string $namespace = 'default', array $tags = array (
)): void`
