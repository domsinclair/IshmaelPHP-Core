<?php
declare(strict_types=1);

namespace Ishmael\Core\Events\Core;

/**
 * Emitted when a user successfully authenticates.
 */
final class AuthenticationSucceeded
{
    public function __construct(
        public readonly array $user,
        public readonly string $guard = 'web'
    ) {}
}
