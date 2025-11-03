<?php
declare(strict_types=1);

namespace Ishmael\Core\Http;

/**
 * Lightweight HTTP Request abstraction wrapping PHP superglobals.
 */
class Request
{
    private string $method;
    private string $uri;
    /** @var array<string, mixed> */
    private array $queryParams;
    /** @var array<string, mixed> */
    private array $parsedBody;
    /** @var array<string, string> */
    private array $headers;
    private string $rawBody;
    /** @var array<string, string> */
    private array $server;

    /**
     * @param array<string,string> $server
     * @param array<string,mixed> $query
     * @param array<string,mixed> $post
     * @param array<string,string> $headers
     */
    public function __construct(
        string $method,
        string $uri,
        array $server = [],
        array $query = [],
        array $post = [],
        array $headers = [],
        string $rawBody = ''
    ) {
        $this->method = strtoupper($method ?: 'GET');
        $this->uri = $uri ?: '/';
        $this->server = $server;
        $this->queryParams = $query;
        $this->parsedBody = $post;
        $this->headers = $headers ?: $this->extractHeadersFromServer($server);
        $this->rawBody = $rawBody;
    }

    public static function fromGlobals(): self
    {
        $server = $_SERVER ?? [];
        $method = (string)($server['REQUEST_METHOD'] ?? 'GET');
        $uri = (string)($server['REQUEST_URI'] ?? '/');
        $query = $_GET ?? [];
        $post = $_POST ?? [];
        $headers = self::serverHeaders($server);
        $rawBody = file_get_contents('php://input') ?: '';
        return new self($method, $uri, $server, $query, $post, $headers, $rawBody);
    }

    /** @return array<string,string> */
    private static function serverHeaders(array $server): array
    {
        $headers = [];
        foreach ($server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
                $headers[$name] = (string)$value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'], true)) {
                $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', $key))));
                $headers[$name] = (string)$value;
            }
        }
        return $headers;
    }

    /** @return array<string,string> */
    private function extractHeadersFromServer(array $server): array
    {
        return self::serverHeaders($server);
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getPath(): string
    {
        $path = parse_url($this->uri, PHP_URL_PATH);
        $path = $path === null ? '/' : (string)$path;
        return $path === '' ? '/' : $path;
    }

    /** @return array<string,mixed> */
    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    /** @return array<string,mixed> */
    public function getParsedBody(): array
    {
        return $this->parsedBody;
    }

    public function getRawBody(): string
    {
        return $this->rawBody;
    }

    /** @return array<string,string> */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getHeader(string $name, ?string $default = null): ?string
    {
        $normalized = $this->normalizeHeaderName($name);
        foreach ($this->headers as $k => $v) {
            if ($this->normalizeHeaderName($k) === $normalized) {
                return $v;
            }
        }
        return $default;
    }

    public function getHost(): string
    {
        $host = $this->getHeader('Host');
        if ($host) {
            // strip port if present
            $pos = strpos($host, ':');
            return $pos !== false ? substr($host, 0, $pos) : $host;
        }
        // Fallbacks
        if (!empty($this->server['SERVER_NAME'])) {
            return (string)$this->server['SERVER_NAME'];
        }
        if (!empty($this->server['SERVER_ADDR'])) {
            return (string)$this->server['SERVER_ADDR'];
        }
        return 'localhost';
    }

    /** @return string[] */
    public function getSubdomainParts(): array
    {
        $host = $this->getHost();
        return $host !== '' ? explode('.', $host) : [];
    }

    /**
     * Convenience getter combining query and parsed body with query taking precedence.
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function input(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $this->queryParams)) {
            return $this->queryParams[$key];
        }
        if (array_key_exists($key, $this->parsedBody)) {
            return $this->parsedBody[$key];
        }
        return $default;
    }

    private function normalizeHeaderName(string $name): string
    {
        return strtolower(str_replace('_', '-', $name));
    }
}
