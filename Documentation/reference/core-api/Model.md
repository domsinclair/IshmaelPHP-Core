# Model

Namespace: `Ishmael\Core`  
Source: `IshmaelPHP-Core\app\Core\Model.php`

Minimal, explicit Model base.

### Public methods
- `delete(string|int $id): int` — Delete a row by its primary key id.
- `find(string|int $id): ?array` — Find a single row by its primary key id.
- `findBy(array $where): array` — Find rows matching the provided where conditions combined with AND.
- `insert(array $data): string|int` — Insert a new row.
- `schema(): ?Ishmael\Core\Database\Schema\TableDefinition` — Optional model-declared schema metadata.
- `update(string|int $id, array $data): int` — Update a row identified by its primary key id.
