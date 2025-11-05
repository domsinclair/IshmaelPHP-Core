# PostgresAdapter

Namespace: `Ishmael\Core\DatabaseAdapters`  
Source: `IshmaelPHP-Core\app\Core\DatabaseAdapters\PostgresAdapter.php`

### Public methods
- `addColumn(string $table, Ishmael\Core\Database\Schema\ColumnDefinition $def): void` — Add a column to an existing table.
- `addIndex(string $table, Ishmael\Core\Database\Schema\IndexDefinition $def): void` — Create an index on a table. Supports unique and partial indexes.
- `alterColumn(string $table, Ishmael\Core\Database\Schema\ColumnDefinition $def): void`
- `beginTransaction(): void`
- `columnExists(string $table, string $column): bool`
- `commit(): void`
- `connect(array $config): PDO` — Connect to a PostgreSQL database using PDO.
- `createTable(Ishmael\Core\Database\Schema\TableDefinition $def): void` — Create a table with a minimal subset of DDL supported by our SchemaManager.
- `disconnect(): void`
- `dropColumn(string $table, string $column): void`
- `dropIndex(string $table, string $name): void`
- `dropTable(string $table): void` — Drop a table if it exists.
- `execute(string $sql, array $params = array (
)): int`
- `getCapabilities(): array`
- `getTableDefinition(string $table): Ishmael\Core\Database\Schema\TableDefinition`
- `inTransaction(): bool`
- `isConnected(): bool`
- `lastInsertId(?string $sequence = NULL): string` — Return the last inserted ID for the current session.
- `query(string $sql, array $params = array (
)): Ishmael\Core\Database\Result`
- `rollBack(): void`
- `runSql(string $sql): void`
- `supportsTransactionalDdl(): bool`
- `tableExists(string $table): bool`
