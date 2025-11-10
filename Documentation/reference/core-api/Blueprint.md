# Blueprint

- FQCN: `Ishmael\Core\Database\Schema\Blueprint`
- Type: class

## Public Methods

- `id()`
- `string(string $name, int $length, bool $nullable, string $default)`
- `text(string $name, bool $nullable, string $default)`
- `boolean(string $name, bool $nullable, bool $default)`
- `timestamps()`
- `index(mixed $columns, string $name)`
- `unique(mixed $columns, string $name)`
- `toTableDefinition()`
