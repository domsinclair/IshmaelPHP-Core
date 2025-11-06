# CorsMiddleware

Namespace: `Ishmael\Core\Http\Middleware`  
Source: `IshmaelPHP-Core\app\Core\Http\Middleware\CorsMiddleware.php`

CorsMiddleware adds CORS headers and can short-circuit preflight OPTIONS requests.

### Public methods
- `__construct(array $config = array (
))`
- `__invoke(Ishmael\Core\Http\Request $req, Ishmael\Core\Http\Response $res, callable $next): Ishmael\Core\Http\Response` — Middleware signature.
