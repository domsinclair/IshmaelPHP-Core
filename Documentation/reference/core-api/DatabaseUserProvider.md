# DatabaseUserProvider

Namespace: `Ishmael\Core\Auth`  
Source: `IshmaelPHP-Core\app\Core\Auth\DatabaseUserProvider.php`

DatabaseUserProvider fetches users from a configured database table and

### Public methods
- `__construct(?array $providerConfig = NULL, ?Ishmael\Core\Auth\HasherInterface $hasher = NULL)`
- `retrieveByCredentials(array $credentials): ?array`
- `retrieveById(mixed $id): ?array`
- `validateCredentials(array $user, array $credentials): bool`
