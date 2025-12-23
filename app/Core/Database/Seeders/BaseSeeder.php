<?php

declare(strict_types=1);

namespace Ishmael\Core\Database\Seeders;

use Ishmael\Core\DatabaseAdapters\DatabaseAdapterInterface;
use Psr\Log\LoggerInterface;

/**
 * Class BaseSeeder
 *
 * Convenience base class for seeders providing a default empty dependsOn() implementation.
 */
abstract class BaseSeeder implements SeederInterface
{
    /**
     * Default: no dependencies.
     *
     * @return string[]
     */
    public function dependsOn(): array
    {
        return [];
    }

    /**
     * Execute the seeder. Implementations must be deterministic and re-runnable.
     *
     * @param DatabaseAdapterInterface $adapter Connected database adapter.
     * @param LoggerInterface $logger Logger for progress reporting.
     * @return void
     */
    abstract public function run(DatabaseAdapterInterface $adapter, LoggerInterface $logger): void;
}
