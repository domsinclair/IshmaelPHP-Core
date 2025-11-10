# Router

- FQCN: `Ishmael\Core\Router`
- Type: class

## Public Methods

- `getLastResponse()`
- `setActive(self $router)`
- `get(string $pattern, mixed $handler, array $middleware)`
- `post(string $pattern, mixed $handler, array $middleware)`
- `put(string $pattern, mixed $handler, array $middleware)`
- `patch(string $pattern, mixed $handler, array $middleware)`
- `delete(string $pattern, mixed $handler, array $middleware)`
- `any(string $pattern, mixed $handler, array $middleware)`
- `group(array $options, callable $callback)`
- `setGlobalMiddleware(array $stack)`
- `useGlobal(array $stack)`
- `addGlobalMiddleware(mixed $mw)`
- `add(array $methods, string $pattern, mixed $handler, array $middleware)`
- `getInstance()`
- `exportCompiledMap()`
- `loadCompiledMap(array $routes)`
- `name(string $routeName)`
- `url(string $name, array $params, array $query, bool $absolute)`
- `generateUrl(string $name, array $params, array $query, bool $absolute)`
- `dispatch(string $uri)`
- `cache(bool $enabled)`
- `buildRoutes()`
