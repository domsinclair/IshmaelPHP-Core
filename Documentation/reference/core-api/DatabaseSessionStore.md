# DatabaseSessionStore

Namespace: `Ishmael\Core\Session`  
Source: `IshmaelPHP-Core\app\Core\Session\DatabaseSessionStore.php`

DatabaseSessionStore persists session payloads in a database table.

### Public methods
- `__construct(string $table = 'sessions')`
- `destroy(string $id): void`
- `generateId(): string`
- `load(string $id): array`
- `persist(string $id, array $data, int $ttlSeconds): void`
