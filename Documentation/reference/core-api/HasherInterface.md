# HasherInterface

Namespace: `Ishmael\Core\Auth`  
Source: `IshmaelPHP-Core\app\Core\Auth\HasherInterface.php`

HasherInterface defines password hashing operations decoupled from PHP's

### Public methods
- `hash(string $plain): string` — Hash a plaintext password using the configured algorithm and options.
- `needsRehash(string $hash): bool` — Whether a stored hash should be rehashed based on current configuration.
- `verify(string $plain, string $hash): bool` — Verify a plaintext password against a stored hash.
