<?php

declare(strict_types=1);

namespace Ishmael\Core;

use RuntimeException;

/**
 * Thrown when a capability check fails, particularly for premium features
 * without a valid license or trial in development.
 */
final class CapabilityException extends RuntimeException
{
}
