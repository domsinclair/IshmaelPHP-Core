<?php
declare(strict_types=1);

namespace Ishmael\Core\Database\Seeding;

use Ishmael\Core\Database\Seeders\SeederRunner; // correct namespace
use Ishmael\Core\DatabaseAdapters\DatabaseAdapterInterface;
use Psr\Log\LoggerInterface;

/**
 * Class SeedManager
 *
 * Thin programmatic entrypoint to run database seeders without a CLI.
 * Wraps SeederRunner and exposes a small, callable API suitable for
 * application bootstrap, tests, and small scripts.
 */
final class SeedManager
{
    private SeederRunner $runner;

    /**
     * Construct a SeedManager.
     *
     * @param DatabaseAdapterInterface $adapter Connected database adapter instance.
     * @param LoggerInterface|null $logger Optional PSR-3 logger to pass to the runner.
     */
    public function __construct(DatabaseAdapterInterface $adapter, ?LoggerInterface $logger = null)
    {
        $this->runner = new SeederRunner($adapter, $logger);
    }

    /**
     * Run seeders for the given module and/or specific class.
     *
     * Environment guard: by default runs only in development/test/local environments. Use $force=true
     * to override (e.g., for CI or special cases). Seeders should be deterministic and re-runnable.
     *
     * @param string|null $module Target module name, or null to run for all modules.
     * @param string|null $class Specific seeder class (FQCN or short name) to run; includes dependencies.
     * @param bool $refresh Hint a refresh run (no bookkeeping; seeders must ensure idempotence).
     * @param string|null $env Environment name to evaluate guard against (defaults from env('APP_ENV')).
     * @param bool $force Override the environment guard.
     * @return void
     */
    public function seed(?string $module = null, ?string $class = null, bool $refresh = false, ?string $env = null, bool $force = false): void
    {
        $this->runner->seed($module, $class, $refresh, $env, $force);
    }
}
