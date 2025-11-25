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
    /** @var array<string,string> */
    private static array $lastHeaders = [];

    /** @param array<string,string> $headers */
    public function __construct(string $body = '', int $statusCode = 200, array $headers = [])
    {
        $this->body = $body;
        $this->statusCode = $statusCode;
        foreach ($headers as $k => $v) {
            $this->headers[(string)$k] = (string)$v;
        }
        self::$lastHeaders = $this->headers;
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

    /**
     * Convenience: create a redirect response with Location header.
     *
     * @param string $location Absolute or relative URL to redirect to
     * @param int $status HTTP status code (default 302)
     * @param array<string,string> $headers Additional headers
     */
    public static function redirect(string $location, int $status = 302, array $headers = []): self
    {
        $headers = ['Location' => $location] + $headers;
        return new self('', $status, $headers);
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

    /**
     * Set or replace a response header.
     */
    public function header(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        self::$lastHeaders = $this->headers;
        return $this;
    }

    /**
     * Convenience: set the ETag header.
     *
     * @param string $etag ETag value; quotes or W/ prefix may be included. If bare value is provided, quotes will be added.
     * @param bool $weak When true and $etag has no prefix, prefixes with W/ to produce a weak ETag.
     * @return self
     */
    public function withEtag(string $etag, bool $weak = false): self
    {
        $etag = trim($etag);
        $isWeak = str_starts_with($etag, 'W/');
        $value = $etag;
        if (!$isWeak) {
            // Ensure quoted
            if (!str_starts_with($etag, '"') && !str_ends_with($etag, '"') && !(preg_match('/^\".*\"$/', $etag) === 1)) {
                $value = '"' . trim($etag, '"') . '"';
            }
            if ($weak) {
                $value = 'W/' . $value;
            }
        }
        return $this->header('ETag', $value);
    }

    /**
     * Convenience: set the Last-Modified header using an RFC7231 date format.
     * @param \DateTimeInterface $dt
     * @return self
     */
    public function withLastModified(\DateTimeInterface $dt): self
    {
        $value = HttpValidators::formatHttpDate($dt);
        return $this->header('Last-Modified', $value);
    }

    /** @return array<string,string> */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /** Testing helper: return last headers set on any Response instance */
    public static function getLastHeaders(): array
    {
        return self::$lastHeaders;
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

    /**
     * Refresh the static last headers snapshot to reflect the current headers on this instance.
     * Useful for ensuring tests that read Response::getLastHeaders() see the final emitted headers
     * when the emitter operates outside of this object.
     */
    public function refreshLastHeadersSnapshot(): void
    {
        self::$lastHeaders = $this->headers;
    }
}
