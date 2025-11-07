# ViewSections

Namespace: `Ishmael\Core`  
Source: `IshmaelPHP-Core\app\Core\ViewSections.php`

ViewSections

### Public methods
- `end(): void` — End the most recently started section capture and store its contents.
- `has(string $name): bool` — Determine whether a section has been defined.
- `set(string $name, string $content, bool $overwrite = true): void` — Set a section's contents programmatically.
- `start(string $name): void` — Begin capturing output for a section with the given name.
- `yield(string $name, string $default = ''): string` — Render (return) a section's contents if defined, else a default value.
