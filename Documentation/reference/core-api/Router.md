# Router

Namespace: `Ishmael\Core`  
Source: `IshmaelPHP-Core\app\Core\Router.php`

Minimal HTTP router supporting fluent route definitions, groups, and module-aware dispatch.

### Public methods
- `add(array $methods, string $pattern, $handler, array $middleware = array (
)): self` — instance API equivalents
- `addGlobalMiddleware($mw): void` — Add a single global middleware.
- `any(string $pattern, $handler, array $middleware = array (
)): self`
- `buildRoutes(): void` — Ensure module route closures have been applied to this router instance.
- `cache(bool $enabled = true): void` — Toggle route caching integration (no-op placeholder; kept for BC).
- `delete(string $pattern, $handler, array $middleware = array (
)): self`
- `dispatch(string $uri): void`
- `exportCompiledMap(): array` — Export the compiled route entries for caching.
- `generateUrl(string $name, array $params = array (
), array $query = array (
), bool $absolute = false): string` — Instance URL generation implementation.
- `get(string $pattern, $handler, array $middleware = array (
)): self`
- `getInstance(): self`
- `getLastResponse(): ?Ishmael\Core\Http\Response`
- `group(array $options, callable $callback): void` — Group routes with options: prefix, middleware, module
- `loadCompiledMap(array $routes): void` — Load a previously compiled route map. This bypasses module route registration.
- `name(string $routeName): self` — Assign a name to the most recently added route for URL generation.
- `patch(string $pattern, $handler, array $middleware = array (
)): self`
- `post(string $pattern, $handler, array $middleware = array (
)): self`
- `put(string $pattern, $handler, array $middleware = array (
)): self`
- `setActive(?self $router): void`
- `setGlobalMiddleware(array $stack): void` — Set the global middleware stack applied to every route.
- `url(string $name, array $params = array (
), array $query = array (
), bool $absolute = false): string` — Generate a URL by route name.
- `useGlobal(array $stack): void` — Static facade to set global middleware on active router.
