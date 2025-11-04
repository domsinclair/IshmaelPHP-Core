# TableDefinition

Namespace: `Ishmael\Core\Database\Schema`  
Source: `IshmaelPHP-Core\app\Core\Database\Schema\TableDefinition.php`

### Public methods
- `__construct(string $name, array $columns = array (
), array $indexes = array (
), array $extras = array (
))`
- `addColumn(Ishmael\Core\Database\Schema\ColumnDefinition $col): self`
- `addIndex(Ishmael\Core\Database\Schema\IndexDefinition $idx): self`
- `jsonSerialize(): array`
- `toArray(): array`
