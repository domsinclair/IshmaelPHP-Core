# Response

Namespace: `Ishmael\Core\Http`  
Source: `IshmaelPHP-Core\app\Core\Http\Response.php`

Lightweight HTTP Response container with helpers.

### Public methods
- `__construct(string $body = '', int $statusCode = 200, array $headers = array (
))`
- `fromThrowable(Throwable $e, bool $debug = false): self`
- `getBody(): string`
- `getHeaders(): array`
- `getLastHeaders(): array` — Testing helper: return last headers set on any Response instance
- `getStatusCode(): int`
- `header(string $name, string $value): self` — Set or replace a response header.
- `html(string $body, int $status = 200, array $headers = array (
)): self`
- `json(mixed $data, int $status = 200, array $headers = array (
)): self`
- `refreshLastHeadersSnapshot(): void` — Refresh the static last headers snapshot to reflect the current headers on this instance.
- `setBody(string $body): self`
- `setStatusCode(int $code): self`
- `text(string $body, int $status = 200, array $headers = array (
)): self`
- `withEtag(string $etag, bool $weak = false): self` — Convenience: set the ETag header.
- `withLastModified(DateTimeInterface $dt): self` — Convenience: set the Last-Modified header using an RFC7231 date format.
