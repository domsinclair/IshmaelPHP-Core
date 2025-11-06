# Gate

Namespace: `Ishmael\Core\Authz`  
Source: `IshmaelPHP-Core\app\Core\Authz\Gate.php`

Gate provides a minimal authorization API to define abilities and

### Public methods
- `__construct()`
- `allows(string $ability, mixed $resource = NULL): bool` — Determine if the given ability is allowed for the current user and resource.
- `authorize(string $ability, mixed $resource = NULL, string $message = 'Forbidden'): void` — Authorize or throw AuthorizationException.
- `define(string $ability, callable $callback): void` — Define a new ability using a callback: fn(?array $user, mixed $resource): bool
- `denies(string $ability, mixed $resource = NULL): bool` — Shortcut for !allows().
