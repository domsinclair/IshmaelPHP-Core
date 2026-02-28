<?php
declare(strict_types=1);

namespace Ishmael\Core\Events\Core;

/**
 * Emitted when a runtime configuration value or feature flag is changed.
 */
final class ConfigurationChanged
{
    public function __construct(
        public readonly string $key,
        public readonly mixed $oldValue,
        public readonly mixed $newValue
    ) {}
}
