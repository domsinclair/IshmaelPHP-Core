# CsrfTokenManager

Namespace: `Ishmael\Core\Security`  
Source: `IshmaelPHP-Core\app\Core\Security\CsrfTokenManager.php`

CsrfTokenManager is responsible for generating, storing, and validating

### Public methods
- `getToken(): string` — Return the current CSRF token for this session, generating one if missing.
- `regenerateToken(): string` — Regenerate the CSRF token and return the new value.
- `rotateOnPrivilegeChange(): void` — Rotate token based on app policy (placeholder for future hooks like login).
- `validateToken(?string $presented): bool` — Validate a presented token against the session token using
