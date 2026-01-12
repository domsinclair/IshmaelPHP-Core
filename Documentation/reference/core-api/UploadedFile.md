# UploadedFile

- FQCN: `Ishmael\Core\Http\UploadedFile`
- Type: class

The `UploadedFile` class wraps an entry from the `$_FILES` array, providing an object-oriented interface for handled uploaded files.

## Public Methods

- `getClientOriginalName(): string`: Returns the original filename sent by the client.
- `getClientMimeType(): string`: Returns the MIME type provided by the client.
- `getSize(): int`: Returns the file size in bytes.
- `getError(): int`: Returns the PHP upload error code.
- `isValid(): bool`: Returns `true` if the upload was successful (`UPLOAD_ERR_OK`).
- `getRealPath(): string`: Returns the temporary path of the uploaded file on disk.
- `moveTo(string $targetPath): bool`: Moves the uploaded file to a new location. Wrapper for `move_uploaded_file()`.
