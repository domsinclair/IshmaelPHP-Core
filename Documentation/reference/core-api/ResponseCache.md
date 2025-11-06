# ResponseCache

Namespace: `Ishmael\Core\Http\Middleware`  
Source: `IshmaelPHP-Core\app\Core\Http\Middleware\ResponseCache.php`

ResponseCache middleware caches full HTTP responses for idempotent requests (GET/HEAD)

### Public methods
- `__construct(array $options = array (
))`
- `__invoke(Ishmael\Core\Http\Request $req, Ishmael\Core\Http\Response $res, callable $next): Ishmael\Core\Http\Response` — Middleware signature: function(Request $req, Response $res, callable $next): Response
- `with(array $options = array (
)): callable` — Static factory to create a middleware callable suitable for router configuration.
