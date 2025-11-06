# PhpPasswordHasher

Namespace: `Ishmael\Core\Auth`  
Source: `IshmaelPHP-Core\app\Core\Auth\PhpPasswordHasher.php`

PhpPasswordHasher implements HasherInterface using PHP's password_* APIs.

### Public methods
- `__construct()`
- `hash(string $plain): string`
- `needsRehash(string $hash): bool`
- `verify(string $plain, string $hash): bool`
