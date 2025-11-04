# Result

Namespace: `Ishmael\Core\Database`  
Source: `IshmaelPHP-Core\app\Core\Database\Result.php`

Lightweight query result wrapper to normalize fetch operations across adapters.

### Public methods
- `__construct(PDOStatement $stmt)`
- `fetch(): array|false` — Fetch the next row from the result set.
- `fetchAll(): array` — Fetch all remaining rows from the result set.
- `rowCount(): int` — Return the number of affected rows (for UPDATE/DELETE) or rows in the result set if supported.
