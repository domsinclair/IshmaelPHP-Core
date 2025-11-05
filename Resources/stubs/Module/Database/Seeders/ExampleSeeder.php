<?php
declare(strict_types=1);

use Ishmael\Core\Database\Seeders\BaseSeeder;
use Ishmael\Core\DatabaseAdapters\DatabaseAdapterInterface;
use Psr\Log\LoggerInterface;

/**
 * Example seeder to demonstrate deterministic, re-runnable seeding.
 */
final class ExampleSeeder extends BaseSeeder
{
    /**
     * Return dependencies if any.
     *
     * @return string[]
     */
    public function dependsOn(): array
    {
        return [];
    }

    /**
     * Insert or update data deterministically.
     *
     * @param DatabaseAdapterInterface $adapter Connected adapter.
     * @param LoggerInterface $logger PSR-3 logger.
     * @return void
     */
    public function run(DatabaseAdapterInterface $adapter, LoggerInterface $logger): void
    {
        // Example: idempotent insert/update logic goes here.
        $logger->info('ExampleSeeder ran (core stub)');
    }
}
