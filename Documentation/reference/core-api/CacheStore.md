# CacheStore

Namespace: `Ishmael\Core\Cache`  
Source: `IshmaelPHP-Core\app\Core\Cache\CacheStore.php`

CacheStore defines a minimal key/value cache with TTL, namespaces, and tags.

### Public methods
- `clearNamespace(string $namespace): void` — Clear all keys in a namespace.
- `clearTag(string $tag, ?string $namespace = NULL): void` — Clear all items that have a given tag.
- `delete(string $key, string $namespace = 'default'): void` — Delete a key.
- `get(string $key, mixed $default = NULL, string $namespace = 'default'): mixed` — Retrieve a value by key or return default when not found/expired.
- `has(string $key, string $namespace = 'default'): bool` — Determine if a key exists and is not expired.
- `purgeExpired(?string $namespace = NULL): void` — Purge expired entries (best-effort, optional for some stores).
- `remember(string $key, callable $callback, ?int $ttlSeconds = NULL, string $namespace = 'default', array $tags = array (
)): mixed` — Remember pattern: return cached value or compute via callback and cache it.
- `set(string $key, mixed $value, ?int $ttlSeconds = NULL, string $namespace = 'default', array $tags = array (
)): void` — Store a value with TTL in seconds. TTL null or <=0 means forever (no expiry).
