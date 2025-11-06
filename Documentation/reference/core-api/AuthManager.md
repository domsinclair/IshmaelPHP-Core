# AuthManager

Namespace: `Ishmael\Core\Auth`  
Source: `IshmaelPHP-Core\app\Core\Auth\AuthManager.php`

AuthManager provides a minimal session-backed authentication API with optional

### Public methods
- `__construct(?Ishmael\Core\Auth\UserProviderInterface $provider = NULL, ?Ishmael\Core\Session\SessionManager $session = NULL)`
- `attempt(array $credentials, bool $remember = false): bool` — Attempt to authenticate using credentials.
- `check(): bool` — Whether a user id is present in the session.
- `guest(): bool` — True when no user is authenticated.
- `id(): string|int|null` — Get the authenticated user's id or null.
- `login(array $user, bool $remember = false): void` — Log the given user in by storing id into the session and rotating id.
- `logout(): void` — Logout the current user, clearing session and remember-me cookie.
- `user(): ?array` — Retrieve the full user record via provider using the id from session.
- `validateRememberToken(string $token): ?string` — Validate and decode a remember-me token. Returns user id on success or null.
