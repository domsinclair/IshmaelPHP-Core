<?php

    declare(strict_types=1);

namespace Ishmael\Core\DatabaseAdapters;

use PDO;
use PDOException;
use Ishmael\Core\Logger;
use Ishmael\Core\Database\Result;
use Ishmael\Core\Database\Schema\TableDefinition;
use Ishmael\Core\Database\Schema\ColumnDefinition;
use Ishmael\Core\Database\Schema\IndexDefinition;

class SQLiteAdapter implements DatabaseAdapterInterface
{
    private ?PDO $pdo = null;
    public function connect(array $config): PDO
    {
        // Determine database path
        $path = $config['database'] ?? base_path('storage/database.sqlite');
    // Ensure the directory exists (except for in-memory ':memory:')
        if ($path !== ':memory:') {
            $dir = dirname($path);
            if (!is_dir($dir)) {
                if (!mkdir($dir, 0777, true) && !is_dir($dir)) {
                    throw new \RuntimeException("Failed to create SQLite directory: {$dir}");
                }
            }
        }

        $dsn = 'sqlite:' . $path;
        try {
            $this->pdo = new PDO($dsn);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        // Reduce lock contention between concurrent processes (e.g., CLI + tests)
            // Note: PDO::ATTR_TIMEOUT is in seconds for SQLite
            if (defined('PDO::ATTR_TIMEOUT')) {
                $this->pdo->setAttribute(PDO::ATTR_TIMEOUT, 5);
            }
            // Apply SQLite PRAGMAs for file-based DBs to allow writers while readers are present
            if ($path !== ':memory:') {
// Wait up to 5000ms if the database is busy
                @($this->pdo->exec('PRAGMA busy_timeout = 5000'));
// Enable WAL so readers do not block writers
                @($this->pdo->exec('PRAGMA journal_mode = WAL'));
// Reasonable durability vs performance for CLI/test usage
                @($this->pdo->exec('PRAGMA synchronous = NORMAL'));
// Enforce FK constraints
                @($this->pdo->exec('PRAGMA foreign_keys = ON'));
            }

            Logger::info("SQLite connected: {$path}");
            return $this->pdo;
        } catch (PDOException $e) {
            Logger::error("SQLite connection failed: " . $e->getMessage());
            throw new \RuntimeException('SQLite connection failed: ' . $e->getMessage());
        }
    }

    public function disconnect(): void
    {
        $this->pdo = null;
    }

    public function isConnected(): bool
    {
        return $this->pdo instanceof PDO;
    }

    public function query(string $sql, array $params = []): Result
    {
        $stmt = $this->requirePdo()->prepare($sql);
        $stmt->execute($params);
        return new Result($stmt);
    }

    public function execute(string $sql, array $params = []): int
    {
        $stmt = $this->requirePdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public function lastInsertId(?string $sequence = null): string
    {
        return $this->requirePdo()->lastInsertId($sequence ?? '');
    }

    public function beginTransaction(): void
    {
        $this->requirePdo()->beginTransaction();
    }

    public function commit(): void
    {
        $this->requirePdo()->commit();
    }

    public function rollBack(): void
    {
        $this->requirePdo()->rollBack();
    }

    public function inTransaction(): bool
    {
        return $this->requirePdo()->inTransaction();
    }

    public function supportsTransactionalDdl(): bool
    {
        // SQLite supports transactional DDL
        return true;
    }

    /**
     * Create a table in SQLite from a TableDefinition.
     *
     * Supports: basic types, NULL/NOT NULL, DEFAULT values, and a single INTEGER PRIMARY KEY AUTOINCREMENT.
     *
     * @param TableDefinition $def Table definition to create.
     */
    public function createTable(TableDefinition $def): void
    {
        $cols = [];
        $autoPkSet = false;
        foreach ($def->columns as $c) {
            if (!$c instanceof ColumnDefinition) {
                continue;
            }
            $type = strtoupper($c->type);
        // SQLite affinity; use INTEGER for auto-increment primary key
            if ($c->autoIncrement && !$autoPkSet) {
                $col = $this->quoteIdent($c->name) . ' INTEGER PRIMARY KEY AUTOINCREMENT';
                $autoPkSet = true;
                $cols[] = $col;
                continue;
            }
            $col = $this->quoteIdent($c->name) . ' ' . $type;
            if ($c->length !== null && !str_contains($type, 'INT')) {
                $col .= '(' . $c->length . ')';
            }
            if ($c->autoIncrement) {
                $col .= ' PRIMARY KEY AUTOINCREMENT';
            }
            if (!$c->nullable) {
                $col .= ' NOT NULL';
            }
            if ($c->default !== null) {
                $default = is_string($c->default) ? "'" . str_replace("'", "''", $c->default) . "'" : (string)$c->default;
                $col .= ' DEFAULT ' . $default;
            }
            $cols[] = $col;
        }
        // Foreign keys (constraints) appended at end of CREATE TABLE
        foreach ($def->foreignKeys as $fk) {
            if (!$fk instanceof \Ishmael\Core\Database\Schema\ForeignKeyDefinition) {
                        continue;
            }
            $local = '(' . implode(', ', array_map(fn($c) => $this->quoteIdent((string)$c), $fk->columns)) . ')';
            $ref = $this->quoteIdent($fk->referencesTable) . ' (' . implode(', ', array_map(fn($c) => $this->quoteIdent((string)$c), $fk->referencesColumns)) . ')';
            $segment = 'FOREIGN KEY ' . $local . ' REFERENCES ' . $ref;
            if ($fk->onDelete) {
                $segment .= ' ON DELETE ' . strtoupper($fk->onDelete);
            }
            if ($fk->onUpdate) {
                $segment .= ' ON UPDATE ' . strtoupper($fk->onUpdate);
            }
            $cols[] = $segment;
        }
        $sql = 'CREATE TABLE ' . $this->quoteIdent($def->name) . ' (' . implode(', ', $cols) . ')';
        $this->runSql($sql);

        // Create non-primary indexes after table creation
        foreach ($def->indexes as $idx) {
            if (!$idx instanceof IndexDefinition) {
                continue;
            }
            if (strtolower($idx->type) === 'primary') {
                continue;
            }
            $this->addIndex($def->name, $idx);
        }
    }

    public function dropTable(string $table): void
    {
        $this->runSql('DROP TABLE IF EXISTS ' . $this->quoteIdent($table));
    }

    public function addColumn(string $table, ColumnDefinition $def): void
    {
        $sql = 'ALTER TABLE ' . $this->quoteIdent($table) . ' ADD COLUMN ' . $this->quoteIdent($def->name) . ' ' . strtoupper($def->type);
        if ($def->default !== null) {
            $default = is_string($def->default) ? "'" . str_replace("'", "''", $def->default) . "'" : (string)$def->default;
            $sql .= ' DEFAULT ' . $default;
        }
        if (!$def->nullable) {
            $sql .= ' NOT NULL';
        }
        $this->runSql($sql);
    }

    public function alterColumn(string $table, ColumnDefinition $def): void
    {
        throw new \LogicException('SQLite cannot alter column definitions directly.');
    }

    public function dropColumn(string $table, string $column): void
    {
        throw new \LogicException('SQLite cannot drop columns directly.');
    }

    public function addIndex(string $table, IndexDefinition $def): void
    {
        $cols = implode(', ', array_map(fn($c) => $this->quoteIdent((string)$c), $def->columns));
        $name = $this->quoteIdent($def->name);
        $tbl = $this->quoteIdent($table);
        $type = strtolower($def->type);
        if ($type === 'primary') {
        // SQLite primary key must be in table definition; skip
            throw new \LogicException('Primary key must be defined at table creation in SQLite.');
        }
        // Use IF NOT EXISTS for idempotent index creation (SQLite supports this clause)
        $prefix = $type === 'unique' ? 'CREATE UNIQUE INDEX IF NOT EXISTS' : 'CREATE INDEX IF NOT EXISTS';
        $sql = "$prefix $name ON $tbl ($cols)";
        if ($def->where) {
        // Partial indexes are supported since SQLite 3.8.0
            $sql .= ' WHERE ' . $def->where;
        }
        $this->runSql($sql);
    }

    public function dropIndex(string $table, string $name): void
    {
        $this->runSql('DROP INDEX IF EXISTS ' . $this->quoteIdent($name));
    }

    public function addForeignKey(string $table, \Ishmael\Core\Database\Schema\ForeignKeyDefinition $def): void
    {
        // SQLite generally requires table rebuilds to add FKs post-creation; be explicit.
        throw new \LogicException('SQLite cannot add foreign keys to existing tables without rebuild. Define FKs at table creation.');
    }

    public function tableExists(string $table): bool
    {
        $stmt = $this->requirePdo()->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name = :n");
        $stmt->execute([':n' => $table]);
        return (bool)$stmt->fetch();
    }

    public function columnExists(string $table, string $column): bool
    {
        $stmt = $this->requirePdo()->query('PRAGMA table_info(' . $this->quoteIdent($table) . ')');
        foreach ($stmt->fetchAll() as $row) {
            if ((string)$row['name'] === $column) {
                return true;
            }
        }
        return false;
    }

    public function getTableDefinition(string $table): TableDefinition
    {
        $def = new TableDefinition($table, [], []);
        $stmt = $this->requirePdo()->query('PRAGMA table_info(' . $this->quoteIdent($table) . ')');
        foreach ($stmt->fetchAll() as $row) {
            $isPk = ((int)($row['pk'] ?? 0)) === 1;
            $type = (string)$row['type'];
            $nullable = ((int)$row['notnull']) === 0;
            if ($isPk) {
        // In SQLite, PRIMARY KEY implies NOT NULL even if PRAGMA reports notnull=0.
                $nullable = false;
            }
            $def->addColumn(new ColumnDefinition(name: (string)$row['name'], type: $type, nullable: $nullable, default: $row['dflt_value'] !== null ? (string)$row['dflt_value'] : null, autoIncrement: $isPk && stripos($type, 'INT') !== false,));
        }
        return $def;
    }

    public function runSql(string $sql): void
    {
        // Make CREATE TABLE idempotent for test friendliness: inject IF NOT EXISTS when missing
        $normalized = ltrim($sql);
        if (preg_match('/^CREATE\s+TABLE\s+(?!IF\s+NOT\s+EXISTS)/i', $normalized) === 1) {
        // Replace the first occurrence of "CREATE TABLE" with "CREATE TABLE IF NOT EXISTS"
            $sql = preg_replace('/^\s*CREATE\s+TABLE\s+/i', 'CREATE TABLE IF NOT EXISTS ', $sql, 1) ?? $sql;
        }
        $this->requirePdo()->exec($sql);
    }

    public function getCapabilities(): array
    {
        return [
            self::CAP_TRANSACTIONAL_DDL,
            // SQLite lacks alter column and drop column via ALTER TABLE
            // self::CAP_ALTER_TABLE_ALTER_COLUMN not included
            // self::CAP_DROP_COLUMN not included
        ];
    }

    private function requirePdo(): PDO
    {
        if (!$this->pdo) {
            throw new \RuntimeException('Adapter not connected');
        }
        return $this->pdo;
    }

    private function quoteIdent(string $name): string
    {
        return '"' . str_replace('"', '""', $name) . '"';
    }
}
