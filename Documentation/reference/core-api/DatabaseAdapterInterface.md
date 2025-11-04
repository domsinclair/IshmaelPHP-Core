# DatabaseAdapterInterface

Namespace: `Ishmael\Core\DatabaseAdapters`  
Source: `IshmaelPHP-Core\app\Core\DatabaseAdapters\DatabaseAdapterInterface.php`

### Public methods
- `addColumn(string $table, Ishmael\Core\Database\Schema\ColumnDefinition $def): void` — Add a column to an existing table.
- `addIndex(string $table, Ishmael\Core\Database\Schema\IndexDefinition $def): void` — Create an index on a table.
- `alterColumn(string $table, Ishmael\Core\Database\Schema\ColumnDefinition $def): void` — Alter a column definition on an existing table. Not all engines support this safely.
- `beginTransaction(): void` — Begin a database transaction.
- `columnExists(string $table, string $column): bool` — Check if a column exists on the specified table.
- `commit(): void` — Commit the current transaction.
- `connect(array $config): PDO` — Establish a database connection and return the PDO instance. Implementations should
- `createTable(Ishmael\Core\Database\Schema\TableDefinition $def): void` — Create a new table according to the provided definition.
- `disconnect(): void` — Disconnect the adapter from the database and release resources.
- `dropColumn(string $table, string $column): void` — Drop a column from a table if supported by the engine.
- `dropIndex(string $table, string $name): void` — Drop an index by its name.
- `dropTable(string $table): void` — Drop a table if it exists.
- `execute(string $sql, array $params = array (
)): int` — Execute a parameterized DML/DDL statement and return the number of affected rows.
- `getCapabilities(): array` — Return a set of capability flags supported by this adapter.
- `getTableDefinition(string $table): Ishmael\Core\Database\Schema\TableDefinition` — Introspect and return a TableDefinition representing the current table.
- `inTransaction(): bool` — Check whether the connection is currently inside a transaction.
- `isConnected(): bool` — Determine whether the adapter currently holds a live PDO connection.
- `lastInsertId(?string $sequence = NULL): string` — Retrieve the last inserted identifier for the current connection/session.
- `query(string $sql, array $params = array (
)): Ishmael\Core\Database\Result` — Prepare and execute a parameterized SQL query and return a Result wrapper for iteration.
- `rollBack(): void` — Roll back the current transaction.
- `runSql(string $sql): void` — Execute a raw SQL statement without parameter binding.
- `supportsTransactionalDdl(): bool` — Whether the database engine supports transactional DDL reliably.
- `tableExists(string $table): bool` — Check if a table exists in the current database/schema.
