# RouteCache

Namespace: `Ishmael\Core`  
Source: `IshmaelPHP-Core\app\Core\RouteCache.php`

RouteCache compiles and loads a deterministic, optimized map of routes for fast production boot.

### Public methods
- `clear(): bool` — Remove the cached routes file if it exists.
- `compile(Ishmael\Core\Router $router, string $modulesPath, bool $force = false): array` — Compile the current Router routes into a cached map with metadata.
- `computeSourceHash(string $modulesPath): array` — Compute a stable hash across all module route source files.
- `isFresh(array $compiled, string $modulesPath): bool` — Determine whether the cache is fresh versus current source files.
- `load(): ?array` — Load cached routes file if present.
- `save(array $compiled): string` — Persist the compiled cache to storage/cache/routes.php
