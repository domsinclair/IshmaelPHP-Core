# SecurityHeaders

Namespace: `Ishmael\Core\Http\Middleware`  
Source: `IshmaelPHP-Core\app\Core\Http\Middleware\SecurityHeaders.php`

SecurityHeaders middleware applies a curated set of HTTP response security headers

### Public methods
- `__construct(array $overrides = array (
))`
- `__invoke(Ishmael\Core\Http\Request $req, Ishmael\Core\Http\Response $res, callable $next): Ishmael\Core\Http\Response` — Middleware entrypoint.
- `disabled(): callable` — Factory helper that returns a middleware callable which is disabled and no-ops.
- `with(array $overrides): callable` — Factory helper to create a middleware callable with per-route overrides.
