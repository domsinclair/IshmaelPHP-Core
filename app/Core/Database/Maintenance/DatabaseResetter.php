<?php

declare(strict_types=1);

namespace Ishmael\Core\Database\Maintenance;

use Ishmael\Core\DatabaseAdapters\DatabaseAdapterInterface;
use Psr\Log\LoggerInterface;

/**
 * DatabaseResetter
 *
 * Development-only helper to purge tables and reset auto-increment/sequence values
 * across supported adapters in a foreign-key safe manner.
 *
 * Notes:
 * - This utility issues adapter-specific SQL via the shared adapter interface; no new
 *   adapter methods are required.
 * - For SQLite, FK checks are temporarily disabled and sqlite_sequence is cleared.
 * - For MySQL, FOREIGN_KEY_CHECKS is disabled and TRUNCATE is used (resets AUTO_INCREMENT).
 * - For PostgreSQL, TRUNCATE ... RESTART IDENTITY CASCADE is used for purge, and sequence
 *   reset uses ALTER SEQUENCE ... RESTART WITH 1 when purge=false.
 */
final class DatabaseResetter
{
    private DatabaseAdapterInterface $adapter;
    private LoggerInterface $logger;
    public function __construct(DatabaseAdapterInterface $adapter, ?LoggerInterface $logger = null)
    {
        $this->adapter = $adapter;
        if ($logger instanceof LoggerInterface) {
            $this->logger = $logger;
        } else {
        // Lightweight null logger to avoid conditional checks
            $this->logger = new class implements LoggerInterface {
                public function emergency($message, array $context = []): void
                {
                }
                public function alert($message, array $context = []): void
                {
                }
                public function critical($message, array $context = []): void
                {
                }
                public function error($message, array $context = []): void
                {
                }
                public function warning($message, array $context = []): void
                {
                }
                public function notice($message, array $context = []): void
                {
                }
                public function info($message, array $context = []): void
                {
                }
                public function debug($message, array $context = []): void
                {
                }
                public function log($level, $message, array $context = []): void
                {
                }
            };
        }
    }

    /**
     * Reset the database state.
     *
     * @param bool $purge When true, truncate all user tables; when false, only reset identities/sequences.
     * @return void
     */
    public function reset(bool $purge = false): void
    {
        $driver = $this->detectDriver();
        $this->logger->info('Database reset start', ['purge' => $purge, 'driver' => $driver]);
        switch ($driver) {
            case 'sqlite':
                $this->resetSqlite($purge);

                break;
            case 'mysql':
            case 'mariadb':
                $this->resetMySql($purge);

                break;
            case 'pgsql':
            case 'postgres':
            case 'postgresql':
                $this->resetPostgres($purge);

                break;
            default:
                        // Best-effort: attempt ANSI TRUNCATE if purge requested

                if ($purge) {
                    foreach ($this->listAllTablesPortable() as $t) {
                        $this->adapter->runSql('TRUNCATE TABLE ' . $this->quoteIdent($t));
                    }
                }

                break;
        }
        $this->logger->info('Database reset complete', ['purge' => $purge, 'driver' => $driver]);
    }

    /**
     * Attempt to detect the underlying PDO driver from the adapter.
     *
     * @return string Lowercased driver name (e.g., sqlite, mysql, pgsql) when determinable.
     */
    private function detectDriver(): string
    {
        try {
// Rely on a trivial query per engine to infer; if it errors, we fallback
            // Prefer environment hint via PHP's PDO::getAttribute if reachable
            $ref = new \ReflectionObject($this->adapter);
            if ($ref->hasMethod('query')) {
            // Try engine-specific no-op to determine
                // Not robust to fetch PDO directly; keep safe and heuristic by class name
                $cls = strtolower($ref->getName());
                if (str_contains($cls, 'sqlite')) {
                    return 'sqlite';
                }
                if (str_contains($cls, 'mysql')) {
                    return 'mysql';
                }
                if (str_contains($cls, 'postgres')) {
                    return 'pgsql';
                }
            }
        } catch (\Throwable $_) {
        // ignore
        }
        return 'unknown';
    }

    private function resetSqlite(bool $purge): void
    {
        // Disable FK checks to allow truncation in arbitrary order
        $this->adapter->runSql('PRAGMA foreign_keys = OFF');
        $tables = $this->listSqliteTables();
        if ($purge) {
            foreach ($tables as $t) {
                $this->adapter->runSql('DELETE FROM ' . $this->quoteIdent($t));
            }
        }
        // Reset AUTOINCREMENT counters in sqlite_sequence if present
        $this->adapter->runSql('PRAGMA foreign_keys = ON');
// Clear sqlite_sequence only after re-enabling FK checks is also fine; it is a meta table
        try {
            $names = $tables;
            if (!empty($names)) {
                $in = implode(',', array_map(fn($n) => "'" . str_replace("'", "''", $n) . "'", $names));
                $this->adapter->runSql('DELETE FROM sqlite_sequence WHERE name IN (' . $in . ')');
            }
        } catch (\Throwable $_) {
        // If sqlite_sequence does not exist (no AUTOINCREMENT used), ignore
        }
    }

    private function resetMySql(bool $purge): void
    {
        $this->adapter->runSql('SET FOREIGN_KEY_CHECKS = 0');
        $tables = $this->listMySqlTables();
        if ($purge) {
            foreach ($tables as $t) {
                $this->adapter->runSql('TRUNCATE TABLE ' . $this->quoteIdent($t));
            }
        } else {
        // Reset AUTO_INCREMENT by ALTER TABLE ... AUTO_INCREMENT = 1
            foreach ($tables as $t) {
                $this->adapter->runSql('ALTER TABLE ' . $this->quoteIdent($t) . ' AUTO_INCREMENT = 1');
            }
        }
        $this->adapter->runSql('SET FOREIGN_KEY_CHECKS = 1');
    }

    private function resetPostgres(bool $purge): void
    {
        $tables = $this->listPostgresTables();
        if ($purge) {
            if (!empty($tables)) {
                $list = implode(', ', array_map(fn($t) => $this->quoteIdent($t), $tables));
        // Truncate all tables with cascade and restart identities
                $this->adapter->runSql('TRUNCATE TABLE ' . $list . ' RESTART IDENTITY CASCADE');
            }
        } else {
        // Reset sequences to 1 (or min value) without truncating data
            $seqs = $this->listPostgresSequences();
            foreach ($seqs as $seq) {
                $this->adapter->runSql('ALTER SEQUENCE ' . $this->quoteIdent($seq) . ' RESTART WITH 1');
            }
        }
    }

    /**
     * List non-system tables in SQLite.
     * @return array<int,string>
     */
    private function listSqliteTables(): array
    {
        $rows = $this->adapter->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")->all();
        return array_values(array_map(fn($r) => (string)$r['name'], $rows));
    }

    /**
     * List base tables in the current MySQL database.
     * @return array<int,string>
     */
    private function listMySqlTables(): array
    {
        $rows = $this->adapter->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->all();
// The column name varies (first column is the table name)
        $tables = [];
        foreach ($rows as $row) {
            $tables[] = (string)array_values($row)[0];
        }
        return $tables;
    }

    /**
     * List tables in the public schema for PostgreSQL.
     * @return array<int,string>
     */
    private function listPostgresTables(): array
    {
        $rows = $this->adapter->query("SELECT tablename FROM pg_tables WHERE schemaname = 'public'")->all();
        return array_values(array_map(fn($r) => (string)$r['tablename'], $rows));
    }

    /**
     * List sequences in the public schema for PostgreSQL.
     * @return array<int,string>
     */
    private function listPostgresSequences(): array
    {
        // Use pg_sequences view available in modern PostgreSQL versions
        try {
            $rows = $this->adapter->query(
                "SELECT schemaname, sequencename FROM pg_sequences WHERE schemaname = 'public'"
            )->all();
            return array_values(
                array_map(
                    fn($r) => ($r['schemaname'] ? ($r['schemaname'] . '.') : '') . (string)$r['sequencename'],
                    $rows
                )
            );
        } catch (\Throwable $e) {
            // Fallback using information_schema
            $rows = $this->adapter->query(
                "SELECT sequence_schema, sequence_name FROM information_schema.sequences WHERE sequence_schema = 'public'"
            )->all();
            return array_values(
                array_map(
                    fn($r) => ($r['sequence_schema'] ? ($r['sequence_schema'] . '.') : '') . (string)$r['sequence_name'],
                    $rows
                )
            );
        }
    }

    /**
     * Portable best-effort table listing using information_schema; may not work for SQLite.
     * @return array<int,string>
     */
    private function listAllTablesPortable(): array
    {
        try {
            $rows = $this->adapter->query(
                "SELECT table_name FROM information_schema.tables WHERE table_schema NOT IN ('information_schema', 'pg_catalog')"
            )->all();
            return array_values(array_map(fn($r) => (string)$r['table_name'], $rows));
        } catch (\Throwable $_) {
            return [];
        }
    }

    private function quoteIdent(string $name): string
    {
        // Minimal quoting that works across adapters used here
        if (str_contains($name, '.')) {
// schema.name -> "schema"."name"
            return '"' . implode('"."', array_map(fn($p) => str_replace('"', '""', $p), explode('.', $name))) . '"';
        }
        return '"' . str_replace('"', '""', $name) . '"';
    }
}
