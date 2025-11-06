# SessionStore

Namespace: `Ishmael\Core\Session`  
Source: `IshmaelPHP-Core\app\Core\Session\SessionStore.php`

SessionStore defines the minimal contract for session persistence backends.

### Public methods
- `destroy(string $id): void` — Destroy/remove a session by id.
- `generateId(): string` — Generate a new cryptographically secure random session identifier.
- `load(string $id): array` — Load the session data for a given session id.
- `persist(string $id, array $data, int $ttlSeconds): void` — Persist the session data at the current id and update expiry if applicable.
