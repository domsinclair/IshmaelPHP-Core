<?php
declare(strict_types=1);

namespace Ishmael\Core\Events\Core;

/**
 * Emitted when an unhandled exception occurs during request processing.
 */
final class RequestFailed
{
    public function __construct(
        public readonly \Throwable $exception,
        public readonly ?\Ishmael\Core\Http\Request $request = null
    ) {}
}
