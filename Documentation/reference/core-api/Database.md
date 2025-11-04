# Database

Namespace: `Ishmael\Core`  
Source: `IshmaelPHP-Core\app\Core\Database.php`

### Public methods
- `adapter(): Ishmael\Core\DatabaseAdapters\DatabaseAdapterInterface` — Get the active DatabaseAdapterInterface.
- `conn(): PDO` — Get the underlying PDO connection. Useful for low-level operations.
- `execute(string $sql, array $params = array (
)): int` — Execute a DML statement (INSERT/UPDATE/DELETE) and return affected row count.
- `init(array $config): void` — Initialize the default database connection and adapter.
- `normalizeParams(array $params): array` — Normalize parameter values for binding. Converts DateTimeInterface to ISO8601 strings,
- `prepare(string $sql): PDOStatement` — Prepare a SQL statement using the underlying PDO connection.
- `query(string $sql, array $params = array (
)): Ishmael\Core\Database\Result` — Execute a SELECT statement and return a Result wrapper. Accepts named or positional params.
- `retryTransaction(int $attempts, int $sleepMs, callable $fn)` — Retry a transaction in case of transient conflicts (e.g., deadlocks or serialization failures).
- `transaction(callable $fn)` — Run a callable inside a transaction boundary with automatic commit/rollback.
