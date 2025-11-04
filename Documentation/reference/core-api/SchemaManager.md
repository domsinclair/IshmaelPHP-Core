# SchemaManager

Namespace: `Ishmael\Core\Database`  
Source: `IshmaelPHP-Core\app\Core\Database\SchemaManager.php`

SchemaManager (lean, explicit)

### Public methods
- `__construct(Ishmael\Core\DatabaseAdapters\DatabaseAdapterInterface $adapter, ?Psr\Log\LoggerInterface $logger = NULL)` — Construct a SchemaManager.
- `applyModuleSchema(string $modulePath): void` — Apply a module's schema.php definitions using conservative synchronization.
- `collectFromModels(array $modelClasses): array` — Utility: Build TableDefinitions from model classes that declare static metadata.
- `diff(string $table, Ishmael\Core\Database\Schema\TableDefinition $desired): Ishmael\Core\Database\Schema\SchemaDiff` — Compute a conservative diff between desired definition and the current database.
- `synchronize(array $defs): void` — Apply safe diffs for a list of table definitions. Unsafe diffs cause an exception with guidance.
