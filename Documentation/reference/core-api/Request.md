# Request

Namespace: `Ishmael\Core\Http`  
Source: `IshmaelPHP-Core\app\Core\Http\Request.php`

Lightweight HTTP Request abstraction wrapping PHP superglobals.

### Public methods
- `__construct(string $method, string $uri, array $server = array (
), array $query = array (
), array $post = array (
), array $headers = array (
), string $rawBody = '')`
- `fromGlobals(): self`
- `getHeader(string $name, ?string $default = NULL): ?string`
- `getHeaders(): array`
- `getHost(): string`
- `getMethod(): string`
- `getParsedBody(): array`
- `getPath(): string`
- `getQueryParams(): array`
- `getRawBody(): string`
- `getSubdomainParts(): array`
- `getUri(): string`
- `input(string $key, mixed $default = NULL): mixed` — Convenience getter combining query and parsed body with query taking precedence.
- `withMethod(string $method): self` — Return a new Request with a replaced HTTP method.
- `withParsedBody(array $body): self` — Return a new Request with a replaced parsed body array.
