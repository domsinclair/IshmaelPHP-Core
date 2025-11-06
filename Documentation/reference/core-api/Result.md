# Result

Namespace: `Ishmael\Core\Database`  
Source: `IshmaelPHP-Core\app\Core\Database\Result.php`

Lightweight query result wrapper to normalize fetch operations across adapters.

### Public methods
- `__construct(PDOStatement $stmt)`
- `all(): array` — BC alias: return all rows as associative arrays.
- `fetch(): array|false` — Fetch the next row from the result set.
- `fetchAll(): array` — Fetch all remaining rows from the result set.
- `fetchAssoc(): ?array` — Fetch a single row as an associative array or null if none.
- `fetchColumn(int $column = 0): mixed` — Fetch the first column of the next row in the result set.
- `first(): ?array` — Convenience: fetch the first row as an associative array or null if none.
- `rowCount(): int` — Return the number of affected rows (for UPDATE/DELETE) or rows in the result set if supported.
