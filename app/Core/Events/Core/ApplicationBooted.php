<?php
declare(strict_types=1);

namespace Ishmael\Core\Events\Core;

/**
 * Emitted when the framework has fully initialized and is ready to serve requests.
 */
final class ApplicationBooted
{
    public function __construct(
        public readonly float $bootTimeMs = 0.0
    ) {}
}
