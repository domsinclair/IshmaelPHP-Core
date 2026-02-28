<?php
declare(strict_types=1);

namespace Ishmael\Core\Events\Core;

/**
 * Emitted after a module has been successfully registered and its services wired.
 */
final class ModuleRegistered
{
    public function __construct(
        public readonly string $name,
        public readonly string $path,
        public readonly array $manifest
    ) {}
}
