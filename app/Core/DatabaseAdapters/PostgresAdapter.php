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

class PostgresAdapter implements DatabaseAdapterInterface
{
    private ?PDO $pdo = null;
/**
     * Connect to a PostgreSQL database using PDO.
     *
     * Supported config keys: host, port, database, username, password, charset.
     *
     * @param array $config Connection parameters.
     * @return PDO Established PDO connection.
     * @throws \RuntimeException If the connection fails.
     */
    public function connect(array $config): PDO
    {
        $dsn = sprintf('pgsql:host=%s;port=%d;dbname=%s', $config['host'] ?? '127.0.0.1', $config['port'] ?? 5432, $config['database'] ?? '');
        try {
            $this->pdo = new PDO($dsn, $config['username'] ?? 'postgres', $config['password'] ?? '', [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
        // Explicitly set charset if supported
            if (isset($config['charset'])) {
                $this->pdo->exec("SET NAMES '" . str_replace("'", "''", (string)$config['charset']) . "'");
            }

                    return $this->pdo;
        } catch (PDOException $e) {
            Logger::error("PostgreSQL connection failed: " . $e->getMessage());
            throw new \RuntimeException('PostgreSQL connection failed');
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

    /**
     * Return the last inserted ID for the current session.
     *
     * In PostgreSQL, PDO::lastInsertId requires a sequence name. If not provided,
     * we will attempt a safe fallback using SELECT LASTVAL(), which returns the most
     * recently assigned sequence value in this session. If neither is available, an
     * empty string may be returned by PDO.
     *
     * @param string|null $sequence Optional sequence name (e.g., table_id_seq).
     * @return string The last insert id as a string.
     */
    public function lastInsertId(?string $sequence = null): string
    {
        if ($sequence) {
            return $this->requirePdo()->lastInsertId($sequence);
        }
        // Fallback to LASTVAL() which is sequence-aware per session
        try {
            $stmt = $this->requirePdo()->query('SELECT LASTVAL() AS id');
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && isset($row['id'])) {
                return (string)$row['id'];
            }
        } catch (\Throwable $e) {
        // ignore and fallback to PDO behavior below
        }
        return $this->requirePdo()->lastInsertId('');
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
        // PostgreSQL supports transactional DDL
        return true;
    }

    /**
     * Create a table with a minimal subset of DDL supported by our SchemaManager.
     *
     * Rules:
     * - autoIncrement true maps to SERIAL (or BIGSERIAL if type suggests bigint) and becomes PRIMARY KEY.
     * - default values are inlined; strings are quoted.
     * - nullable controls NOT NULL.
     *
     * @param TableDefinition $def
     */
    public function createTable(TableDefinition $def): void
    {
        $cols = [];
        $primary = null;
        foreach ($def->columns as $c) {
            if (!$c instanceof ColumnDefinition) {
                continue;
            }
            $type = strtoupper($c->type);
            if ($c->autoIncrement && $primary === null) {
            // Choose SERIAL/BIGSERIAL
                $pgType = str_contains($type, 'BIG') ? 'BIGSERIAL' : 'SERIAL';
                $cols[] = $this->quoteIdent($c->name) . ' ' . $pgType;
                $primary = $c->name;
                continue;
            }
            $col = $this->quoteIdent($c->name) . ' ' . $this->renderType($c, $type);
            if (!$c->nullable) {
                $col .= ' NOT NULL';
            }
            if ($c->default !== null) {
                $col .= ' DEFAULT ' . $this->renderDefault($c->default);
            }
            $cols[] = $col;
        }
        if ($primary !== null) {
            $cols[] = 'PRIMARY KEY (' . $this->quoteIdent($primary) . ')';
        }
        // Append foreign key constraints
        foreach ($def->foreignKeys as $fk) {
            if (!$fk instanceof \Ishmael\Core\Database\Schema\ForeignKeyDefinition) {
                continue;
            }
            $local = '(' . implode(', ', array_map(fn($c) => $this->quoteIdent((string)$c), $fk->columns)) . ')';
            $refCols = implode(', ', array_map(fn($c) => $this->quoteIdent((string)$c), $fk->referencesColumns));
            $ref = $this->quoteIdent($fk->referencesTable) . ' (' . $refCols . ')';
            $seg = 'FOREIGN KEY ' . $local . ' REFERENCES ' . $ref;
            if ($fk->onDelete) {
                $seg .= ' ON DELETE ' . strtoupper($fk->onDelete);
            }
            if ($fk->onUpdate) {
                $seg .= ' ON UPDATE ' . strtoupper($fk->onUpdate);
            }
            $cols[] = $seg;
        }
        $sql = 'CREATE TABLE ' . $this->quoteIdent($def->name) . ' (' . implode(', ', $cols) . ')';
        $this->runSql($sql);

        // Create indexes (non-primary) after table creation
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
    /**
     * Drop a table if it exists.
     *
     * @param string $table Table name.
     */
    public function dropTable(string $table): void
    {
        $this->runSql('DROP TABLE IF EXISTS ' . $this->quoteIdent($table));
    }
    /**
     * Add a column to an existing table.
     *
     * Note: Adding auto-increment columns post-creation is not supported here; use explicit migrations.
     *
     * @param string $table Table name.
     * @param ColumnDefinition $def Column definition to add.
     */
    public function addColumn(string $table, ColumnDefinition $def): void
    {
        if ($def->autoIncrement) {
            throw new \LogicException('Adding auto-increment columns requires explicit sequence; write a migration.');
        }
        $type = strtoupper($def->type);
        $col = $this->quoteIdent($def->name) . ' ' . $this->renderType($def, $type);
        if (!$def->nullable) {
            $col .= ' NOT NULL';
        }
        if ($def->default !== null) {
            $col .= ' DEFAULT ' . $this->renderDefault($def->default);
        }
        $this->runSql('ALTER TABLE ' . $this->quoteIdent($table) . ' ADD COLUMN ' . $col);
    }
    public function alterColumn(string $table, ColumnDefinition $def): void
    {
        throw new \LogicException('Use explicit migrations for complex ALTER COLUMN in Postgres.');
    }
    public function dropColumn(string $table, string $column): void
    {
        $this->runSql('ALTER TABLE ' . $this->quoteIdent($table) . ' DROP COLUMN ' . $this->quoteIdent($column));
    }
    /**
     * Create an index on a table. Supports unique and partial indexes.
     */
    public function addIndex(string $table, IndexDefinition $def): void
    {
        $type = strtolower($def->type);
        if ($type === 'primary') {
            $cols = implode(', ', array_map(fn($c) => $this->quoteIdent((string)$c), $def->columns));
            $sql = 'ALTER TABLE ' . $this->quoteIdent($table) . ' ADD PRIMARY KEY (' . $cols . ')';
            $this->runSql($sql);
            return;
        }
        $unique = $type === 'unique' ? 'UNIQUE ' : '';
        $name = $this->quoteIdent($def->name);
        $cols = implode(', ', array_map(fn($c) => $this->quoteIdent((string)$c), $def->columns));
        $sql = 'CREATE ' . $unique . 'INDEX ' . $name . ' ON ' . $this->quoteIdent($table) . ' (' . $cols . ')';
        if ($def->where) {
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
        // Adding FKs post-creation can require access exclusive locks; keep conservative for now.
        throw new \LogicException('Adding foreign keys post-creation requires an explicit migration in Postgres.');
    }
    public function tableExists(string $table): bool
    {
        $stmt = $this->requirePdo()->prepare("SELECT to_regclass(:t) AS t");
        $stmt->execute([':t' => $table]);
        $row = $stmt->fetch();
        return !empty($row['t']);
    }
    public function columnExists(string $table, string $column): bool
    {
        $stmt = $this->requirePdo()->prepare('SELECT 1 FROM information_schema.columns WHERE table_name = :t AND column_name = :c');
        $stmt->execute([':t' => $table, ':c' => $column]);
        return (bool)$stmt->fetch();
    }
    public function getTableDefinition(string $table): TableDefinition
    {
        throw new \LogicException('Introspection not yet implemented for Postgres');
    }

    public function runSql(string $sql): void
    {
        $this->requirePdo()->exec($sql);
    }

    public function getCapabilities(): array
    {
        return [
            self::CAP_ALTER_TABLE_ALTER_COLUMN,
            self::CAP_DROP_COLUMN,
            self::CAP_PARTIAL_INDEX,
            self::CAP_CONCURRENT_INDEX,
            self::CAP_SCHEMAS,
            self::CAP_TRANSACTIONAL_DDL,
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
     * Render a PostgreSQL type from a ColumnDefinition and normalized upper type.
     */
    private function renderType(ColumnDefinition $c, string $upperType): string
    {
        switch (true) {
            case str_contains($upperType, 'BIGINT'):
                return 'BIGINT';
            case $upperType === 'INT' || str_contains($upperType, 'INTEGER'):
                return 'INTEGER';
            case str_starts_with($upperType, 'VARCHAR'):
                $len = $c->length ?? 255;

                return 'VARCHAR(' . $len . ')';
            case str_starts_with($upperType, 'CHAR'):
                $len = $c->length ?? 1;

                return 'CHAR(' . $len . ')';
            case $upperType === 'TEXT' || str_contains($upperType, 'TEXT'):
                return 'TEXT';
            case $upperType === 'TIMESTAMP' || $upperType === 'DATETIME':
                return 'TIMESTAMP';
            case $upperType === 'DATE':
                return 'DATE';
            case str_starts_with($upperType, 'DECIMAL') || $upperType === 'NUMERIC':
                $p = $c->precision ?? 10;
                $s = $c->scale ?? 0;

                return 'DECIMAL(' . $p . ',' . $s . ')';
            case $upperType === 'BOOLEAN' || $upperType === 'BOOL':
                return 'BOOLEAN';
            default:
                if ($c->length !== null && preg_match('/^\w+$/', $c->type)) {
                    return $c->type . '(' . $c->length . ')';
                }

                return $c->type;
        }
    }

    /**
     * Render a default value literal for PostgreSQL.
     */
    private function renderDefault(mixed $value): string
    {
        if (is_string($value)) {
            return "'" . str_replace("'", "''", $value) . "'";
        }
        if (is_bool($value)) {
            return $value ? 'TRUE' : 'FALSE';
        }
        if ($value === null) {
            return 'NULL';
        }
        return (string)$value;
    }

    private function quoteIdent(string $name): string
    {
        return '"' . str_replace('"', '""', $name) . '"';
    }
}
