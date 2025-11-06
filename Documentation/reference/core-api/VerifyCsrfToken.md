# VerifyCsrfToken

Namespace: `Ishmael\Core\Http\Middleware`  
Source: `IshmaelPHP-Core\app\Core\Http\Middleware\VerifyCsrfToken.php`

VerifyCsrfToken middleware enforces CSRF protection on state-changing requests.

### Public methods
- `__construct(array $override = array (
))`
- `__invoke(Ishmael\Core\Http\Request $req, Ishmael\Core\Http\Response $res, callable $next): Ishmael\Core\Http\Response` — Middleware entrypoint.
