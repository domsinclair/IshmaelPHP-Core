# DatabaseAdapterInterface

Namespace: `Ishmael\Core\DatabaseAdapters`  
Source: `IshmaelPHP-Core\app\Core\DatabaseAdapters\DatabaseAdapterInterface.php`

### Public methods
- `addColumn(string $table, Ishmael\Core\Database\Schema\ColumnDefinition $def): void`
- `addIndex(string $table, Ishmael\Core\Database\Schema\IndexDefinition $def): void`
- `alterColumn(string $table, Ishmael\Core\Database\Schema\ColumnDefinition $def): void`
- `beginTransaction(): void`
- `columnExists(string $table, string $column): bool`
- `commit(): void`
- `connect(array $config): PDO` — Establish and return a PDO connection; adapters should keep an internal reference too.
- `createTable(Ishmael\Core\Database\Schema\TableDefinition $def): void`
- `disconnect(): void`
- `dropColumn(string $table, string $column): void`
- `dropIndex(string $table, string $name): void`
- `dropTable(string $table): void`
- `execute(string $sql, array $params = array (
)): int`
- `getCapabilities(): array` — Return a set of capability flags supported by this adapter.
- `getTableDefinition(string $table): Ishmael\Core\Database\Schema\TableDefinition`
- `inTransaction(): bool`
- `isConnected(): bool`
- `lastInsertId(?string $sequence = NULL): string`
- `query(string $sql, array $params = array (
)): Ishmael\Core\Database\Result`
- `rollBack(): void`
- `runSql(string $sql): void`
- `supportsTransactionalDdl(): bool`
- `tableExists(string $table): bool`
