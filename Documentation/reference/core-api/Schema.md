# Schema

Namespace: `Ishmael\Core\Database\Schema`  
Source: `IshmaelPHP-Core\app\Core\Database\Schema\Schema.php`

Schema facade

### Public methods
- `create(string $table, callable $callback): void` — Create a new table using a Blueprint callback.
- `dropIfExists(string $table): void` — Drop a table if it exists.
- `table(string $table, callable $callback): void` — Modify an existing table by adding any columns defined in the Blueprint that
