# Migrator

Namespace: `Ishmael\Core\Database\Migrations`  
Source: `IshmaelPHP-Core\app\Core\Database\Migrations\Migrator.php`

Class Migrator

### Public methods
- `__construct(Ishmael\Core\DatabaseAdapters\DatabaseAdapterInterface $adapter, ?Psr\Log\LoggerInterface $logger = NULL)` — Construct a Migrator.
- `migrate(?string $module = NULL, int $steps = 0, bool $pretend = false, bool $force = false): void` — Apply pending migrations.
- `reset(?string $module = NULL): void` — Reset all applied migrations.
- `rollback(?string $module = NULL, int $steps = 1): void` — Roll back migrations.
- `status(?string $module = NULL): array` — Get migration status information.
