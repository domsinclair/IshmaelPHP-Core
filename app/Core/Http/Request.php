<?php
declare(strict_types=1);

namespace Ishmael\Core\Http;

/**
 * Minimal HTTP Request wrapper. This is intentionally tiny for Kernel v1
 * and will be expanded in Phase 2 Task 2.x.
 */
class Request
{
    public function __construct(
        public string $method,
        public string $uri
    ) {}

    public static function fromGlobals(): self
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        return new self($method, $uri);
    }
}
