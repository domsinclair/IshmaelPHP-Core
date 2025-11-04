# DatabaseAdapterFactory

Namespace: `Ishmael\Core\DatabaseAdapters`  
Source: `IshmaelPHP-Core\app\Core\DatabaseAdapters\DatabaseAdapterFactory.php`

Factory for registering and creating database adapter instances by driver name.

### Public methods
- `create(string $driver): Ishmael\Core\DatabaseAdapters\DatabaseAdapterInterface` — Get a new adapter instance for a driver.
- `register(string $driver, string $class): void` — Register a new adapter class under a given driver key.
- `registerDefaults(): void` — Register default adapters.
