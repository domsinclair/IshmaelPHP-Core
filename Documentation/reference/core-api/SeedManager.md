# SeedManager

Namespace: `Ishmael\Core\Database\Seeding`  
Source: `IshmaelPHP-Core\app\Core\Database\Seeding\SeedManager.php`

Class SeedManager

### Public methods
- `__construct(Ishmael\Core\DatabaseAdapters\DatabaseAdapterInterface $adapter, ?Psr\Log\LoggerInterface $logger = NULL)` — Construct a SeedManager.
- `seed(?string $module = NULL, ?string $class = NULL, bool $refresh = false, ?string $env = NULL, bool $force = false): void` — Run seeders for the given module and/or specific class.
