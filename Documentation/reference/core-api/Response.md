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
- `header(string $name, string $value): self`
- `html(string $body, int $status = 200, array $headers = array (
)): self`
- `json(mixed $data, int $status = 200, array $headers = array (
)): self`
- `setBody(string $body): self`
- `setStatusCode(int $code): self`
- `text(string $body, int $status = 200, array $headers = array (
)): self`
