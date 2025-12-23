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

class MySQLAdapter implements DatabaseAdapterInterface
{
    private ?PDO $pdo = null;
    public function connect(array $config): PDO
    {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $config['host'] ?? '127.0.0.1', $config['database'] ?? '', $config['charset'] ?? 'utf8mb4');
        try {
            $this->pdo = new PDO($dsn, $config['username'] ?? 'root', $config['password'] ?? '', [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
            return $this->pdo;
        } catch (PDOException $e) {
            Logger::error("MySQL connection failed: " . $e->getMessage());
            throw new \RuntimeException('MySQL connection failed');
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
        return $this->requirePdo()->lastInsertId();
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
        // MySQL transactional DDL depends on engine; assume false for safety
        return false;
    }

    public function createTable(TableDefinition $def): void
    {
        $cols = [];
        foreach ($def->columns as $c) {
            if (!$c instanceof ColumnDefinition) {
                continue;
            }
            $col = $this->quoteIdent($c->name) . ' ' . $this->mapType($c);
            if (!$c->nullable) {
                $col .= ' NOT NULL';
            }
            if ($c->default !== null) {
                $default = is_string($c->default) ? "'" . str_replace("'", "''", $c->default) . "'" : (string)$c->default;
                $col .= ' DEFAULT ' . $default;
            }
            if ($c->autoIncrement) {
                $col .= ' AUTO_INCREMENT';
            }
            $cols[] = $col;
        }
        // If there is a single autoIncrement INT column, make it primary key implicitly
        $pk = null;
        foreach ($def->columns as $c) {
            if ($c instanceof ColumnDefinition && $c->autoIncrement) {
                        $pk = $c->name;
                        break;
            }
        }
        if ($pk) {
            $cols[] = 'PRIMARY KEY (' . $this->quoteIdent($pk) . ')';
        }
        // Append foreign key constraints
        foreach ($def->foreignKeys as $fk) {
            if (!$fk instanceof \Ishmael\Core\Database\Schema\ForeignKeyDefinition) {
                continue;
            }
            $local = '(' . implode(', ', array_map(fn($c) => $this->quoteIdent((string)$c), $fk->columns)) . ')';
            $ref = $this->quoteIdent($fk->referencesTable) . ' (' . implode(', ', array_map(fn($c) => $this->quoteIdent((string)$c), $fk->referencesColumns)) . ')';
            $seg = 'FOREIGN KEY ' . $local . ' REFERENCES ' . $ref;
            if ($fk->onDelete) {
                $seg .= ' ON DELETE ' . strtoupper($fk->onDelete);
            }
            if ($fk->onUpdate) {
                $seg .= ' ON UPDATE ' . strtoupper($fk->onUpdate);
            }
            $cols[] = $seg;
        }
        $sql = 'CREATE TABLE ' . $this->quoteIdent($def->name) . ' (' . implode(', ', $cols) . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
        $this->runSql($sql);
    }
    public function dropTable(string $table): void
    {
        $this->runSql('DROP TABLE IF EXISTS ' . $this->quoteIdent($table));
    }
    public function addColumn(string $table, ColumnDefinition $def): void
    {
        $sql = 'ALTER TABLE ' . $this->quoteIdent($table) . ' ADD COLUMN ' . $this->quoteIdent($def->name) . ' ' . $this->mapType($def);
        if (!$def->nullable) {
            $sql .= ' NOT NULL';
        }
        if ($def->default !== null) {
            $default = is_string($def->default) ? "'" . str_replace("'", "''", $def->default) . "'" : (string)$def->default;
            $sql .= ' DEFAULT ' . $default;
        }
        $this->runSql($sql);
    }
    public function alterColumn(string $table, ColumnDefinition $def): void
    {
        throw new \LogicException('Altering columns is unsafe; write an explicit migration for MySQL.');
    }
    public function dropColumn(string $table, string $column): void
    {
        $this->runSql('ALTER TABLE ' . $this->quoteIdent($table) . ' DROP COLUMN ' . $this->quoteIdent($column));
    }
    public function addIndex(string $table, IndexDefinition $def): void
    {
        $type = strtolower($def->type);
        if ($type === 'primary') {
            $sql = 'ALTER TABLE ' . $this->quoteIdent($table) . ' ADD PRIMARY KEY (' . implode(', ', array_map(fn($c) => $this->quoteIdent((string)$c), $def->columns)) . ')';
        } elseif ($type === 'unique') {
            $sql = 'CREATE UNIQUE INDEX ' . $this->quoteIdent($def->name) . ' ON ' . $this->quoteIdent($table) . ' (' . implode(', ', array_map(fn($c) => $this->quoteIdent((string)$c), $def->columns)) . ')';
        } else {
            $sql = 'CREATE INDEX ' . $this->quoteIdent($def->name) . ' ON ' . $this->quoteIdent($table) . ' (' . implode(', ', array_map(fn($c) => $this->quoteIdent((string)$c), $def->columns)) . ')';
        }
        $this->runSql($sql);
    }
    public function dropIndex(string $table, string $name): void
    {
        $this->runSql('DROP INDEX ' . $this->quoteIdent($name) . ' ON ' . $this->quoteIdent($table));
    }
    public function addForeignKey(string $table, \Ishmael\Core\Database\Schema\ForeignKeyDefinition $def): void
    {
        // For safety in this phase, require explicit migrations for adding FKs after table creation.
        throw new \LogicException('Adding foreign keys post-creation requires an explicit migration in MySQL.');
    }
    public function tableExists(string $table): bool
    {
        $stmt = $this->requirePdo()->prepare('SHOW TABLES LIKE :t');
        $stmt->execute([':t' => $table]);
        return (bool)$stmt->fetch();
    }
    public function columnExists(string $table, string $column): bool
    {
        $stmt = $this->requirePdo()->prepare('SHOW COLUMNS FROM ' . $this->quoteIdent($table) . ' LIKE :c');
        $stmt->execute([':c' => $column]);
        return (bool)$stmt->fetch();
    }
    public function getTableDefinition(string $table): TableDefinition
    {
        throw new \LogicException('Introspection not yet implemented for MySQL');
    }

    public function runSql(string $sql): void
    {
        $this->requirePdo()->exec($sql);
    }

    public function getCapabilities(): array
    {
        return [
            // MySQL can drop columns; altering column definitions is not uniformly safe
            self::CAP_DROP_COLUMN,
            // No transactional DDL guaranteed
        ];
    }

    private function requirePdo(): PDO
    {
        if (!$this->pdo) {
            throw new \RuntimeException('Adapter not connected');
        }
        return $this->pdo;
    }

    /**
     * Map a ColumnDefinition to a MySQL column type clause.
     *
     * Supports common types: INT, BIGINT, VARCHAR(n), CHAR(n), TEXT, DATETIME, TIMESTAMP, BOOLEAN (as TINYINT(1)),
     * DECIMAL(p,s). Falls back to raw type if unrecognized.
     *
     * @param ColumnDefinition $c
     * @return string
     */
    private function mapType(ColumnDefinition $c): string
    {
        $t = strtoupper($c->type);
        switch (true) {
            case str_contains($t, 'BIGINT'):
                return 'BIGINT' . ($c->unsigned ? ' UNSIGNED' : '');
            case $t === 'INT' || str_contains($t, 'INTEGER'):
                return 'INT' . ($c->unsigned ? ' UNSIGNED' : '');
            case str_starts_with($t, 'VARCHAR'):
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       $len = $c->length ?? 255;

                return 'VARCHAR(' . $len . ')';
            case str_starts_with($t, 'CHAR'):
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       $len = $c->length ?? 1;

                return 'CHAR(' . $len . ')';
            case $t === 'TEXT' || str_contains($t, 'TEXT'):
                return 'TEXT';
            case $t === 'DATETIME':
                return 'DATETIME';
            case $t === 'TIMESTAMP':
                return 'TIMESTAMP';
            case $t === 'DATE':
                return 'DATE';
            case str_starts_with($t, 'DECIMAL') || $t === 'NUMERIC':
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       $p = $c->precision ?? 10;
                $s = $c->scale ?? 0;

                return 'DECIMAL(' . $p . ',' . $s . ')';
            case $t === 'BOOLEAN' || $t === 'BOOL':
                return 'TINYINT(1)';
            default:
                        // Allow passthrough, append length if provided

                if ($c->length !== null && preg_match('/^\\w+$/', $c->type)) {
                    return $c->type . '(' . $c->length . ')';
                }

                return $c->type;
        }
    }

    private function quoteIdent(string $name): string
    {
        return '`' . str_replace('`', '``', $name) . '`';
    }
}
