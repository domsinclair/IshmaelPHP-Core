<?php
declare(strict_types=1);

namespace Ishmael\Core\Http;

/**
 * Minimal HTTP Response container for Kernel v1.
 */
class Response
{
    private int $statusCode = 200;
    /** @var array<string,string> */
    private array $headers = [];
    private string $body = '';

    public function __construct(string $body = '', int $statusCode = 200, array $headers = [])
    {
        $this->body = $body;
        $this->statusCode = $statusCode;
        foreach ($headers as $k => $v) {
            $this->headers[(string)$k] = (string)$v;
        }
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
