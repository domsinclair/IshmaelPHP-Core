# ConfigCache

Namespace: `Ishmael\Core`  
Source: `IshmaelPHP-Core\app\Core\ConfigCache.php`

ConfigCache compiles and loads a merged configuration repository for fast boot.

### Public methods
- `cachePath(): string` — Return absolute cache file path.
- `clear(): bool` — Delete the cache file if it exists.
- `compile(array $dirs): array` — Compile configuration from multiple directories into a single repository.
- `computeSourceHash(array $dirs): array` — Compute a stable hash across all provided config directories and files.
- `getDefaultDirs(): array` — Return the default configuration directories in priority order.
- `isFresh(array $compiled, array $dirs): bool` — Determine freshness by comparing stored hash with recalculated one for the same sources.
- `load(): ?array` — Load the compiled cache file if present.
- `save(array $compiled): string` — Save the compiled cache to disk and return the path.
