<?php
declare(strict_types=1);

namespace Ishmael\Core\Http;

/**
 * Lightweight HTTP Response container with helpers.
 */
class Response
{
    private int $statusCode = 200;
    /** @var array<string,string> */
    private array $headers = [];
    private string $body = '';

    /** @param array<string,string> $headers */
    public function __construct(string $body = '', int $statusCode = 200, array $headers = [])
    {
        $this->body = $body;
        $this->statusCode = $statusCode;
        foreach ($headers as $k => $v) {
            $this->headers[(string)$k] = (string)$v;
        }
    }

    public static function text(string $body, int $status = 200, array $headers = []): self
    {
        $headers = ['Content-Type' => 'text/plain; charset=UTF-8'] + $headers;
        return new self($body, $status, $headers);
    }

    /**
     * @param mixed $data Serializable data
     * @param int $status
     * @param array<string,string> $headers
     */
    public static function json(mixed $data, int $status = 200, array $headers = []): self
    {
        $body = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($body === false) {
            $body = 'null';
        }
        $headers = ['Content-Type' => 'application/json; charset=UTF-8'] + $headers;
        return new self($body, $status, $headers);
    }

    public static function html(string $body, int $status = 200, array $headers = []): self
    {
        $headers = ['Content-Type' => 'text/html; charset=UTF-8'] + $headers;
        return new self($body, $status, $headers);
    }

    public static function fromThrowable(\Throwable $e, bool $debug = false): self
    {
        $safeMessage = 'Internal Server Error';
        if ($debug) {
            $body = '<h1>Internal Server Error</h1>'
                . '<p>' . htmlspecialchars($e->getMessage()) . '</p>'
                . '<pre>' . htmlspecialchars((string)$e) . '</pre>';
            return self::html($body, 500);
        }
        return self::html('<h1>' . $safeMessage . '</h1>', 500);
    }

    public function setStatusCode(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function header(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /** @return array<string,string> */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function setBody(string $body): self
    {
        $this->body = $body;
        return $this;
    }

    public function getBody(): string
    {
        return $this->body;
    }
}
