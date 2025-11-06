# UserProviderInterface

Namespace: `Ishmael\Core\Auth`  
Source: `IshmaelPHP-Core\app\Core\Auth\UserProviderInterface.php`

UserProviderInterface abstracts user retrieval to decouple storage choice.

### Public methods
- `retrieveByCredentials(array $credentials): ?array` — Retrieve a user by provided credentials (e.g., username/email).
- `retrieveById(mixed $id): ?array` — Retrieve a user by its unique identifier.
- `validateCredentials(array $user, array $credentials): bool` — Validate a user's credentials using the configured hasher.
