# Blueprint

Namespace: `Ishmael\Core\Database\Schema`  
Source: `IshmaelPHP-Core\app\Core\Database\Schema\Blueprint.php`

Blueprint is a minimal schema builder used within migrations to describe a table.

### Public methods
- `__construct(string $table)`
- `boolean(string $name, bool $nullable = false, ?bool $default = NULL): self` — Define a BOOLEAN column.
- `id(): self` — Add an auto-incrementing primary key named "id".
- `index(array|string $columns, ?string $name = NULL): self` — Add a simple index on one or more columns.
- `string(string $name, int $length = 255, bool $nullable = false, ?string $default = NULL): self` — Define a VARCHAR/TEXT-like column.
- `text(string $name, bool $nullable = false, ?string $default = NULL): self` — Define a TEXT column.
- `timestamps(): self` — Convenience to add created_at and updated_at timestamp columns.
- `toTableDefinition(): Ishmael\Core\Database\Schema\TableDefinition` — Convert to a TableDefinition for adapter consumption.
- `unique(array|string $columns, ?string $name = NULL): self` — Add a unique index on one or more columns.
