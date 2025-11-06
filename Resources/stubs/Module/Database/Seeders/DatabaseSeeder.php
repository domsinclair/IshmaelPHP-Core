<?php
declare(strict_types=1);

use Ishmael\Core\Database\Seeders\BaseSeeder;
use Ishmael\Core\DatabaseAdapters\DatabaseAdapterInterface;
use Psr\Log\LoggerInterface;

/**
 * DatabaseSeeder (module entrypoint)
 * ---------------------------------
 * Coordinates module-specific seeders. You may add dependencies via dependsOn().
 */
final class DatabaseSeeder extends BaseSeeder
{
    /**
     * Declare dependent seeder classes to run before this one.
     *
     * @return string[] FQCNs of seeders this seeder depends on.
     */
    public function dependsOn(): array
    {
        return [
            ExampleSeeder::class,
        ];
    }

    /**
     * Run the seeding logic for this module.
     *
     * @param DatabaseAdapterInterface $adapter Connected adapter.
     * @param LoggerInterface $logger PSR-3 logger.
     * @return void
     */
    public function run(DatabaseAdapterInterface $adapter, LoggerInterface $logger): void
    {
        $logger->info('DatabaseSeeder completed (core stub)');
    }
}
