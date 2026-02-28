<?php
declare(strict_types=1);

namespace Ishmael\Core\Events\Core;

/**
 * Emitted when an authentication attempt fails.
 */
final class AuthenticationFailed
{
    public function __construct(
        public readonly array $credentials,
        public readonly string $guard = 'web'
    ) {}
}
