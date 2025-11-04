<?php
declare(strict_types=1);

namespace Ishmael\Core\Database\Migrations;

use DateTimeImmutable;
use Ishmael\Core\Logger;
use Ishmael\Core\DatabaseAdapters\DatabaseAdapterInterface;
use Psr\Log\LoggerInterface;

/**
 * MigrationRunner discovers and executes module-first migrations with bookkeeping.
 *
 * Filesystem layout per module:
 *   Modules/<Module>/Database/Migrations/
 * Migration file pattern:
 *   YYYYMMDDHHMMSS_Description.php defining a class extending BaseMigration with up()/down().
 *
 * Bookkeeping table: ishmael_migrations (id, module, name, batch, applied_at)
 * All operations are idempotent and ordered; transactional per migration when supported.
 */
/**
 * Class MigrationRunner
 *
 * Provides programmatic API to run module-first migrations with idempotent bookkeeping.
 * Public API:
 *  - migrate(?string $module = null, int $steps = 0, bool $pretend = false): void
 *  - rollback(?string $module = null, int $steps = 1): void
 *  - reset(?string $module = null): void
 *  - status(?string $module = null): array
 */
final class MigrationRunner
{
    private DatabaseAdapterInterface $adapter;
    private LoggerInterface $logger;

    /**
     * Construct a MigrationRunner.
     *
     * @param DatabaseAdapterInterface $adapter Active database adapter (connected)
     * @param LoggerInterface|null $logger Optional PSR-3 logger; if null, resolved from app() or falls back to NullChannel
     */
    public function __construct(DatabaseAdapterInterface $adapter, ?LoggerInterface $logger = null)
    {
        $this->adapter = $adapter;
        if ($logger) {
            $this->logger = $logger;
        } else {
            $resolved = null;
            if (function_exists('app')) {
                $resolved = app(\Psr\Log\LoggerInterface::class) ?? app('logger');
            }
            $this->logger = $resolved instanceof LoggerInterface ? $resolved : new \Ishmael\Core\Log\NullChannel();
        }
    }

    /**
     * Run pending migrations. If $module is null, runs for all discovered modules.
     * If $steps > 0 and $module is provided, limits to that many migrations.
     */
    /**
     * Apply pending migrations in ascending timestamp order.
     *
     * If a module is provided, only that module's migrations are considered. Otherwise, all discovered
     * modules are processed. When $steps > 0 and a module is provided, only the first $steps pending
     * migrations for that module will be executed. When $pretend is true, no changes are applied; the
     * runner only logs what would be executed.
     */
    public function migrate(?string $module = null, int $steps = 0, bool $pretend = false): void
    {
        $this->ensureBookkeepingTable();
        $modules = $module ? [$module] : $this->discoverModules();
        $batch = $this->nextBatchNumber();
        $this->logger->info('Starting migration batch', ['batch' => $batch, 'modules' => $modules, 'pretend' => $pretend]);

        foreach ($modules as $mod) {
            $pending = $this->pendingMigrations($mod);
            if ($steps > 0) {
                $pending = array_slice($pending, 0, $steps);
            }
            foreach ($pending as $mig) {
                [$name, $file] = $mig;
                $this->logger->info('Running migration', ['module' => $mod, 'name' => $name, 'batch' => $batch, 'pretend' => $pretend]);
                if ($pretend) {
                    continue;
                }
                $instance = $this->instantiateMigration($file);
                $this->executeSafely(function() use ($instance) { $instance->up(); });
                $this->recordApplied($mod, $name, $batch);
                $this->logger->info('Finished migration', ['module' => $mod, 'name' => $name]);
            }
        }
        $this->logger->info('Finished migration batch', ['batch' => $batch]);
    }

    /**
     * Rollback the last $steps migrations for a module (or all modules by latest batch if null).
     */
    /**
     * Roll back migrations.
     *
     * When a module is provided, rolls back the last $steps migrations applied for that module.
     * When module is null, rolls back the latest batch across all modules (classic framework behavior).
     */
    public function rollback(?string $module = null, int $steps = 1): void
    {
        $this->ensureBookkeepingTable();
        if ($module) {
            $toRollback = $this->appliedMigrations($module, $steps);
        } else {
            // Rollback the latest batch across all modules
            $latestBatch = $this->latestBatchNumber();
            $toRollback = $this->appliedByBatch($latestBatch);
        }
        if (!$toRollback) { return; }
        $this->logger->info('Starting rollback', ['module' => $module, 'count' => count($toRollback)]);
        foreach ($toRollback as $row) {
            $mod = $row['module'];
            $name = $row['name'];
            $file = $this->findMigrationFile($mod, $name);
            if (!$file) {
                $this->logger->error('Migration file not found for rollback', ['module' => $mod, 'name' => $name]);
                continue;
            }
            $this->logger->info('Rolling back migration', ['module' => $mod, 'name' => $name]);
            $instance = $this->instantiateMigration($file);
            $this->executeSafely(function() use ($instance) { $instance->down(); });
            $this->removeAppliedRecord($mod, $name);
            $this->logger->info('Rolled back migration', ['module' => $mod, 'name' => $name]);
        }
        $this->logger->info('Finished rollback');
    }

    /** Reset all migrations (rollback everything). */
    public function reset(?string $module = null): void
    {
        $this->ensureBookkeepingTable();
        $rows = $module ? $this->appliedByModuleAll($module) : $this->appliedAll();
        // Reverse chronological (latest first)
        usort($rows, function($a, $b){
            return [$b['batch'], $b['id']] <=> [$a['batch'], $a['id']];
        });
        foreach ($rows as $row) {
            $mod = $row['module'];
            $name = $row['name'];
            $file = $this->findMigrationFile($mod, $name);
            if (!$file) { continue; }
            $instance = $this->instantiateMigration($file);
            $this->executeSafely(function() use ($instance) { $instance->down(); });
            $this->removeAppliedRecord($mod, $name);
        }
        $this->logger->info('Reset complete', ['module' => $module]);
    }

    /**
     * Return status array of migrations with applied flag.
     * @return array<string,array<int,array{name:string,applied:bool,batch:int|null,applied_at:string|null}>>
     */
    public function status(?string $module = null): array
    {
        $modules = $module ? [$module] : $this->discoverModules();
        $out = [];
        foreach ($modules as $mod) {
            $all = $this->discoverMigrations($mod);
            $applied = $this->appliedByModuleLookup($mod);
            $items = [];
            foreach ($all as [$name, $_file]) {
                $row = $applied[$name] ?? null;
                $items[] = [
                    'name' => $name,
                    'applied' => $row !== null,
                    'batch' => $row['batch'] ?? null,
                    'applied_at' => $row['applied_at'] ?? null,
                ];
            }
            $out[$mod] = $items;
        }
        return $out;
    }

    // ----- internals -----

    private function ensureBookkeepingTable(): void
    {
        // Try a SQLite-compatible definition first
        $sqlSqlite = "CREATE TABLE IF NOT EXISTS ishmael_migrations (\n" .
            "id INTEGER PRIMARY KEY,\n" .
            "module VARCHAR(100) NOT NULL,\n" .
            "name VARCHAR(255) NOT NULL,\n" .
            "batch INTEGER NOT NULL,\n" .
            "applied_at DATETIME NOT NULL\n" .
        ")";
        try {
            $this->adapter->runSql($sqlSqlite);
        } catch (\Throwable $e) {
            // Fallback to MySQL syntax
            $sqlMysql = "CREATE TABLE IF NOT EXISTS ishmael_migrations (\n" .
                "id INT UNSIGNED NOT NULL AUTO_INCREMENT,\n" .
                "`module` VARCHAR(100) NOT NULL,\n" .
                "`name` VARCHAR(255) NOT NULL,\n" .
                "`batch` INT NOT NULL,\n" .
                "`applied_at` DATETIME NOT NULL,\n" .
                "PRIMARY KEY (id)\n" .
            ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            $this->adapter->runSql($sqlMysql);
        }
    }

    private function nextBatchNumber(): int
    {
        try {
            $res = $this->adapter->query('SELECT MAX(batch) AS m FROM ishmael_migrations');
            $row = $res->first();
            $max = $row && isset($row['m']) ? (int)$row['m'] : 0;
            return $max + 1;
        } catch (\Throwable $_) {
            return 1;
        }
    }

    private function latestBatchNumber(): int
    {
        $res = $this->adapter->query('SELECT MAX(batch) AS m FROM ishmael_migrations');
        $row = $res->first();
        return $row && isset($row['m']) ? (int)$row['m'] : 0;
    }

    private function recordApplied(string $module, string $name, int $batch): void
    {
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $this->adapter->execute('INSERT INTO ishmael_migrations (module,name,batch,applied_at) VALUES (:m,:n,:b,:t)', [
            ':m' => $module, ':n' => $name, ':b' => $batch, ':t' => $now,
        ]);
    }

    private function removeAppliedRecord(string $module, string $name): void
    {
        $this->adapter->execute('DELETE FROM ishmael_migrations WHERE module = :m AND name = :n', [':m' => $module, ':n' => $name]);
    }

    /** @return array<array{id:int,module:string,name:string,batch:int,applied_at:string}> */
    private function appliedByBatch(int $batch): array
    {
        if ($batch <= 0) { return []; }
        return $this->adapter->query('SELECT id,module,name,batch,applied_at FROM ishmael_migrations WHERE batch = :b ORDER BY id DESC', [':b' => $batch])->all();
    }

    /** @return array<array{id:int,module:string,name:string,batch:int,applied_at:string}> */
    private function appliedByModuleAll(string $module): array
    {
        return $this->adapter->query('SELECT id,module,name,batch,applied_at FROM ishmael_migrations WHERE module = :m ORDER BY batch DESC, id DESC', [':m' => $module])->all();
    }

    /** @return array<array{id:int,module:string,name:string,batch:int,applied_at:string}> */
    private function appliedAll(): array
    {
        return $this->adapter->query('SELECT id,module,name,batch,applied_at FROM ishmael_migrations')->all();
    }

    /**
     * Return the last $steps applied rows for a module, latest first.
     * @return array<array{id:int,module:string,name:string,batch:int,applied_at:string}>
     */
    private function appliedMigrations(string $module, int $steps): array
    {
        $sql = 'SELECT id,module,name,batch,applied_at FROM ishmael_migrations WHERE module = :m ORDER BY batch DESC, id DESC';
        $rows = $this->adapter->query($sql, [':m' => $module])->all();
        return array_slice($rows, 0, max(0, $steps));
    }

    /** @return array<string,array{batch:int,applied_at:string}> */
    private function appliedByModuleLookup(string $module): array
    {
        $rows = $this->adapter->query('SELECT name,batch,applied_at FROM ishmael_migrations WHERE module = :m', [':m' => $module])->all();
        $map = [];
        foreach ($rows as $r) { $map[$r['name']] = ['batch' => (int)$r['batch'], 'applied_at' => (string)$r['applied_at']]; }
        return $map;
    }

    /**
     * Discover modules present under known roots.
     * @return string[] Module names
     */
    private function discoverModules(): array
    {
        $roots = [base_path('Modules'), base_path('SkeletonApp/Modules')];
        $mods = [];
        foreach ($roots as $root) {
            if (!is_dir($root)) { continue; }
            foreach (scandir($root) ?: [] as $entry) {
                if ($entry === '.' || $entry === '..') { continue; }
                if (is_dir($root . DIRECTORY_SEPARATOR . $entry)) {
                    $mods[$entry] = true;
                }
            }
        }
        ksort($mods);
        return array_keys($mods);
    }

    /** @return array<int,array{0:string,1:string}> [name, file] in ascending order */
    private function discoverMigrations(string $module): array
    {
        $dirs = [
            base_path('Modules/' . $module . '/Database/Migrations'),
            base_path('SkeletonApp/Modules/' . $module . '/Database/Migrations'),
        ];
        $files = [];
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) { continue; }
            foreach (scandir($dir) ?: [] as $f) {
                if (preg_match('/^\\d{14}_.+\\.php$/', $f)) {
                    $files[$f] = $dir . DIRECTORY_SEPARATOR . $f;
                }
            }
        }
        ksort($files, SORT_STRING);
        $out = [];
        foreach ($files as $name => $file) { $out[] = [$name, $file]; }
        return $out;
    }

    /** @return array<int,array{0:string,1:string}> */
    private function pendingMigrations(string $module): array
    {
        $all = $this->discoverMigrations($module);
        $applied = $this->appliedByModuleLookup($module);
        $out = [];
        foreach ($all as $pair) {
            if (!isset($applied[$pair[0]])) { $out[] = $pair; }
        }
        return $out;
    }

    private function findMigrationFile(string $module, string $name): ?string
    {
        foreach ($this->discoverMigrations($module) as [$n, $file]) {
            if ($n === $name) { return $file; }
        }
        return null;
    }

    private function instantiateMigration(string $file): BaseMigration
    {
        $before = get_declared_classes();
        require_once $file;
        $after = get_declared_classes();
        $diff = array_values(array_diff($after, $before));
        foreach (array_reverse($diff) as $class) { // newest first
            if (is_subclass_of($class, BaseMigration::class)) {
                /** @var BaseMigration $obj */
                $obj = new $class();
                $obj->setAdapter($this->adapter);
                return $obj;
            }
        }
        throw new \RuntimeException("No migration class found in file: {$file}");
    }

    /** Wrap execution in a transaction when supported. */
    private function executeSafely(callable $fn): void
    {
        if ($this->adapter->supportsTransactionalDdl()) {
            $this->adapter->beginTransaction();
            try { $fn(); $this->adapter->commit(); }
            catch (\Throwable $e) { $this->adapter->rollBack(); throw $e; }
        } else {
            $this->logger->warning('Transactional DDL not supported; running migration without transaction');
            $fn();
        }
    }
}
