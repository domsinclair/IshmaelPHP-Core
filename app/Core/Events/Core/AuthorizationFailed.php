<?php
declare(strict_types=1);

namespace Ishmael\Core\Events\Core;

/**
 * Emitted when a user attempts an action without sufficient permissions.
 */
final class AuthorizationFailed
{
    public function __construct(
        public readonly ?array $user,
        public readonly string $ability,
        public readonly mixed $resource = null
    ) {}
}
