<?php
declare(strict_types=1);

namespace Ishmael\Core\Events\Core;

/**
 * Emitted when a feature pack has been removed from the system.
 */
final class FeaturePackUninstalled
{
    public function __construct(
        public readonly string $name
    ) {}
}
