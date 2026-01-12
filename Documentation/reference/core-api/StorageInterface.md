# StorageInterface

- FQCN: `Ishmael\Core\Support\StorageInterface`
- Type: interface

The `StorageInterface` defines the contract for storage abstractions, allowing the application to be storage-agnostic (e.g., Local Disk, Amazon S3).

## Methods

- `put(string $path, mixed $contents): bool`: Store the content at the given path.
- `get(string $path): ?string`: Retrieve the contents of a file.
- `exists(string $path): bool`: Check if a file exists.
- `delete(string $path): bool`: Delete a file.
- `size(string $path): int`: Get the file size in bytes.
- `mimeType(string $path): string`: Get the MIME type of a file.
- `url(string $path): string`: Get the public URL for the given path.
- `path(string $path): string`: Get the absolute path for the given path (if local).
