# CookieSessionStore

Namespace: `Ishmael\Core\Session`  
Source: `IshmaelPHP-Core\app\Core\Session\CookieSessionStore.php`

CookieSessionStore stores the entire session payload in a single encrypted, HMAC-signed cookie.

### Public methods
- `__construct(string $cookieName, string $appKey)`
- `destroy(string $id): void`
- `generateId(): string`
- `load(string $id): array`
- `persist(string $id, array $data, int $ttlSeconds): void`
