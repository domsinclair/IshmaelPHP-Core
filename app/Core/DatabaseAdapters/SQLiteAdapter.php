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

        public function createTable(TableDefinition $def): void
        {
            // Minimal implementation: build simple CREATE TABLE with basic columns
            $cols = [];
            foreach ($def->columns as $c) {
                if (!$c instanceof ColumnDefinition) continue;
                $col = $this->quoteIdent($c->name) . ' ' . strtoupper($c->type);
                if ($c->length !== null && !str_contains(strtoupper($c->type), 'INT')) {
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
            $sql = 'CREATE TABLE ' . $this->quoteIdent($def->name) . ' (' . implode(', ', $cols) . ')';
            $this->runSql($sql);
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
            $prefix = $type === 'unique' ? 'CREATE UNIQUE INDEX' : 'CREATE INDEX';
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
                if ((string)$row['name'] === $column) return true;
            }
            return false;
        }

        public function getTableDefinition(string $table): TableDefinition
        {
            $def = new TableDefinition($table, [], []);
            $stmt = $this->requirePdo()->query('PRAGMA table_info(' . $this->quoteIdent($table) . ')');
            foreach ($stmt->fetchAll() as $row) {
                $def->addColumn(new ColumnDefinition(
                    name: (string)$row['name'],
                    type: (string)$row['type'],
                    nullable: ((int)$row['notnull']) === 0,
                    default: $row['dflt_value'] !== null ? (string)$row['dflt_value'] : null,
                    autoIncrement: false,
                ));
            }
            return $def;
        }

        public function runSql(string $sql): void
        {
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
