<?php

declare(strict_types=1);

namespace Ishmael\Core\Support;

interface StorageInterface
{
    public function put(string $path, mixed $contents): bool;

    public function get(string $path): ?string;

    public function exists(string $path): bool;

    public function delete(string $path): bool;

    public function size(string $path): int;

    public function mimeType(string $path): string;

    public function url(string $path): string;

    public function path(string $path): string;
}
