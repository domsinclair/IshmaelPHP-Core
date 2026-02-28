<?php
declare(strict_types=1);

namespace Ishmael\Core\Events\Core;

/**
 * Emitted when a feature pack has been installed and any migrations have completed.
 */
final class FeaturePackInstalled
{
    public function __construct(
        public readonly string $name,
        public readonly string $version,
        public readonly array $manifest = []
    ) {}
}
