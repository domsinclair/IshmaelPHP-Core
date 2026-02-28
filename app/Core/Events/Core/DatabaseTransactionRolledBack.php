<?php
declare(strict_types=1);

namespace Ishmael\Core\Events\Core;

/**
 * Emitted when a database transaction is rolled back.
 */
final class DatabaseTransactionRolledBack
{
    public function __construct(
        public readonly string $connection = 'default',
        public readonly ?\Throwable $reason = null
    ) {}
}
