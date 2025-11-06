# SessionManager

Namespace: `Ishmael\Core\Session`  
Source: `IshmaelPHP-Core\app\Core\Session\SessionManager.php`

SessionManager coordinates a concrete SessionStore and provides

### Public methods
- `__construct(Ishmael\Core\Session\SessionStore $store, ?string $id, int $ttlSeconds)`
- `advanceFlash(): void` — Promote next flash to now and clear old now bucket.
- `all(): array`
- `flash(string $key, mixed $value): void` — Set a flash message to be available for the next request only.
- `forgetFlash(string $key): void` — Remove current flash value (useful after consumption in views/APIs).
- `get(string $key, mixed $default = NULL): mixed`
- `getFlash(string $key, mixed $default = NULL): mixed` — Retrieve a flash value from the current request lifecycle.
- `getId(): string`
- `has(string $key): bool`
- `invalidate(): void` — Destroy the session entirely.
- `persistIfDirty(): void` — Persist only if mutated or lifecycle advanced.
- `put(string $key, mixed $value): void`
- `regenerateId(): void` — Regenerate the session identifier (session fixation defense).
- `remove(string $key): void`
