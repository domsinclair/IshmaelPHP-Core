# Router

Namespace: `Ishmael\Core`  
Source: `IshmaelPHP-Core\app\Core\Router.php`

Minimal HTTP router supporting fluent route definitions, groups, and module-aware dispatch.

### Public methods
- `add(array $methods, string $pattern, $handler, array $middleware = array (
)): self` — instance API equivalents
- `any(string $pattern, $handler, array $middleware = array (
)): self`
- `cache(bool $enabled = true): void`
- `delete(string $pattern, $handler, array $middleware = array (
)): self`
- `dispatch(string $uri): void`
- `get(string $pattern, $handler, array $middleware = array (
)): self`
- `getInstance(): self`
- `getLastResponse(): ?Ishmael\Core\Http\Response`
- `group(array $options, callable $callback): void` — Group routes with options: prefix, middleware, module
- `patch(string $pattern, $handler, array $middleware = array (
)): self`
- `post(string $pattern, $handler, array $middleware = array (
)): self`
- `put(string $pattern, $handler, array $middleware = array (
)): self`
- `setActive(?self $router): void`
