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
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                $config['host'] ?? '127.0.0.1',
                $config['database'] ?? '',
                $config['charset'] ?? 'utf8mb4'
            );

            try {
                $this->pdo = new PDO(
                    $dsn,
                    $config['username'] ?? 'root',
                    $config['password'] ?? '',
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    ]
                );
                return $this->pdo;
            } catch (PDOException $e) {
                Logger::error("MySQL connection failed: " . $e->getMessage());
                throw new \RuntimeException('MySQL connection failed');
            }
        }

        public function disconnect(): void { $this->pdo = null; }
        public function isConnected(): bool { return $this->pdo instanceof PDO; }

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

        public function beginTransaction(): void { $this->requirePdo()->beginTransaction(); }
        public function commit(): void { $this->requirePdo()->commit(); }
        public function rollBack(): void { $this->requirePdo()->rollBack(); }
        public function inTransaction(): bool { return $this->requirePdo()->inTransaction(); }

        public function supportsTransactionalDdl(): bool
        {
            // MySQL transactional DDL depends on engine; assume false for safety
            return false;
        }

        public function createTable(TableDefinition $def): void
        { throw new \LogicException('Schema operations not yet implemented for MySQL'); }
        public function dropTable(string $table): void
        { $this->runSql('DROP TABLE IF EXISTS ' . $this->quoteIdent($table)); }
        public function addColumn(string $table, ColumnDefinition $def): void
        { throw new \LogicException('Schema operations not yet implemented for MySQL'); }
        public function alterColumn(string $table, ColumnDefinition $def): void
        { throw new \LogicException('Schema operations not yet implemented for MySQL'); }
        public function dropColumn(string $table, string $column): void
        { $this->runSql('ALTER TABLE ' . $this->quoteIdent($table) . ' DROP COLUMN ' . $this->quoteIdent($column)); }
        public function addIndex(string $table, IndexDefinition $def): void
        { throw new \LogicException('Schema operations not yet implemented for MySQL'); }
        public function dropIndex(string $table, string $name): void
        { $this->runSql('DROP INDEX ' . $this->quoteIdent($name) . ' ON ' . $this->quoteIdent($table)); }
        public function tableExists(string $table): bool
        { $stmt = $this->requirePdo()->prepare('SHOW TABLES LIKE :t'); $stmt->execute([':t'=>$table]); return (bool)$stmt->fetch(); }
        public function columnExists(string $table, string $column): bool
        { $stmt = $this->requirePdo()->prepare('SHOW COLUMNS FROM ' . $this->quoteIdent($table) . ' LIKE :c'); $stmt->execute([':c'=>$column]); return (bool)$stmt->fetch(); }
        public function getTableDefinition(string $table): TableDefinition
        { throw new \LogicException('Introspection not yet implemented for MySQL'); }

        public function runSql(string $sql): void { $this->requirePdo()->exec($sql); }

        public function getCapabilities(): array
        {
            return [
                self::CAP_ALTER_TABLE_ALTER_COLUMN,
                self::CAP_DROP_COLUMN,
                // No transactional DDL guaranteed
            ];
        }

        private function requirePdo(): PDO
        {
            if (!$this->pdo) { throw new \RuntimeException('Adapter not connected'); }
            return $this->pdo;
        }

        private function quoteIdent(string $name): string
        {
            return '`' . str_replace('`', '``', $name) . '`';
        }
    }
