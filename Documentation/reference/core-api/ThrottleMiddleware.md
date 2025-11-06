# ThrottleMiddleware

Namespace: `Ishmael\Core\Http\Middleware`  
Source: `IshmaelPHP-Core\app\Core\Http\Middleware\ThrottleMiddleware.php`

ThrottleMiddleware implements a token bucket rate limiter using the configured CacheStore.

### Public methods
- `__construct(array $options = array (
))`
- `__invoke(Ishmael\Core\Http\Request $req, Ishmael\Core\Http\Response $res, callable $next): Ishmael\Core\Http\Response` — Middleware signature: function(Request $req, Response $res, callable $next): Response
- `with(array $options = array (
)): callable` — Static factory for router configuration.
