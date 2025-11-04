# SchemaDiff

Namespace: `Ishmael\Core\Database\Schema`  
Source: `IshmaelPHP-Core\app\Core\Database\Schema\SchemaDiff.php`

Value object representing a conservative schema diff.

### Public methods
- `__construct(string $table)`
- `addUnsafe(string $message): void` — Add an unsafe change message.
- `hasChanges(): bool` — Whether there is any change to apply (safe or unsafe recorded).
- `isSafe(): bool` — Whether this diff contains only safe, auto-applicable operations.
- `jsonSerialize(): array`
- `toArray(): array`
