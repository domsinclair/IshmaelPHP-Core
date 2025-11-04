<?php
    declare(strict_types=1);

    namespace Ishmael\Core\DatabaseAdapters;

    use PDO;
    use Ishmael\Core\Database\Result;
    use Ishmael\Core\Database\Schema\TableDefinition;
    use Ishmael\Core\Database\Schema\ColumnDefinition;
    use Ishmael\Core\Database\Schema\IndexDefinition;

    interface DatabaseAdapterInterface
    {
        // Capability flags (string identifiers for simplicity)
        public const CAP_ALTER_TABLE_ALTER_COLUMN = 'alter_table.alter_column';
        public const CAP_ALTER_TABLE_RENAME_COLUMN = 'alter_table.rename_column';
        public const CAP_DROP_COLUMN = 'alter_table.drop_column';
        public const CAP_ADD_COLUMN_AFTER = 'alter_table.add_column_after';
        public const CAP_PARTIAL_INDEX = 'index.partial';
        public const CAP_CONCURRENT_INDEX = 'index.concurrent';
        public const CAP_SCHEMAS = 'namespaces.schemas';
        public const CAP_TRANSACTIONAL_DDL = 'ddl.transactional';

        /**
         * Establish and return a PDO connection; adapters should keep an internal reference too.
         */
        public function connect(array $config): PDO;
        public function disconnect(): void;
        public function isConnected(): bool;

        // Querying / DML
        public function query(string $sql, array $params = []): Result;
        public function execute(string $sql, array $params = []): int;
        public function lastInsertId(?string $sequence = null): string;

        // Transactions
        public function beginTransaction(): void;
        public function commit(): void;
        public function rollBack(): void;
        public function inTransaction(): bool;
        public function supportsTransactionalDdl(): bool;

        // Schema operations (minimal set)
        public function createTable(TableDefinition $def): void;
        public function dropTable(string $table): void;

        public function addColumn(string $table, ColumnDefinition $def): void;
        public function alterColumn(string $table, ColumnDefinition $def): void;
        public function dropColumn(string $table, string $column): void;

        public function addIndex(string $table, IndexDefinition $def): void;
        public function dropIndex(string $table, string $name): void;

        public function tableExists(string $table): bool;
        public function columnExists(string $table, string $column): bool;
        public function getTableDefinition(string $table): TableDefinition;

        // Raw escape hatch
        public function runSql(string $sql): void;

        /**
         * Return a set of capability flags supported by this adapter.
         *
         * @return array<int, string>
         */
        public function getCapabilities(): array;
    }
