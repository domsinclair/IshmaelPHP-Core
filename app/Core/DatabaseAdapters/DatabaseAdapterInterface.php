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
         * Establish a database connection and return the PDO instance. Implementations should
         * also store the PDO internally to service subsequent calls.
         *
         * Expected keys in $config vary by adapter but commonly include: host, port, database,
         * username, password, and charset.
         *
         * @param array $config Connection parameters for the specific adapter/driver.
         * @return PDO Connected PDO instance.
         * @throws \RuntimeException If the connection cannot be established.
         */
        public function connect(array $config): PDO;

        /**
         * Disconnect the adapter from the database and release resources.
         */
        public function disconnect(): void;

        /**
         * Determine whether the adapter currently holds a live PDO connection.
         *
         * @return bool True if connected; false otherwise.
         */
        public function isConnected(): bool;

        /**
         * Prepare and execute a parameterized SQL query and return a Result wrapper for iteration.
         *
         * @param string $sql SQL statement with positional or named placeholders.
         * @param array<int|string,mixed> $params Bound parameter values.
         * @return Result Result wrapper around the executed PDOStatement.
         */
        public function query(string $sql, array $params = []): Result;

        /**
         * Execute a parameterized DML/DDL statement and return the number of affected rows.
         *
         * @param string $sql SQL to execute.
         * @param array<int|string,mixed> $params Parameters to bind.
         * @return int Number of affected rows as reported by PDOStatement::rowCount().
         */
        public function execute(string $sql, array $params = []): int;

        /**
         * Retrieve the last inserted identifier for the current connection/session.
         *
         * For engines like PostgreSQL, a sequence name may be required to obtain a value.
         *
         * @param string|null $sequence Optional sequence name when required by the engine.
         * @return string The last insert id as a string (empty string if unavailable).
         */
        public function lastInsertId(?string $sequence = null): string;

        /**
         * Begin a database transaction.
         */
        public function beginTransaction(): void;

        /**
         * Commit the current transaction.
         */
        public function commit(): void;

        /**
         * Roll back the current transaction.
         */
        public function rollBack(): void;

        /**
         * Check whether the connection is currently inside a transaction.
         *
         * @return bool True if inside a transaction.
         */
        public function inTransaction(): bool;

        /**
         * Whether the database engine supports transactional DDL reliably.
         *
         * @return bool True if DDL statements participate in transactions.
         */
        public function supportsTransactionalDdl(): bool;

        /**
         * Create a new table according to the provided definition.
         *
         * @param TableDefinition $def Declarative table definition.
         */
        public function createTable(TableDefinition $def): void;

        /**
         * Drop a table if it exists.
         *
         * @param string $table Table name (unqualified).
         */
        public function dropTable(string $table): void;

        /**
         * Add a column to an existing table.
         *
         * @param string $table Table name.
         * @param ColumnDefinition $def Column definition.
         */
        public function addColumn(string $table, ColumnDefinition $def): void;

        /**
         * Alter a column definition on an existing table. Not all engines support this safely.
         *
         * @param string $table Table name.
         * @param ColumnDefinition $def Column definition (new desired state).
         */
        public function alterColumn(string $table, ColumnDefinition $def): void;

        /**
         * Drop a column from a table if supported by the engine.
         *
         * @param string $table Table name.
         * @param string $column Column name.
         */
        public function dropColumn(string $table, string $column): void;

        /**
         * Create an index on a table.
         *
         * @param string $table Table name.
         * @param IndexDefinition $def Index definition.
         */
        public function addIndex(string $table, IndexDefinition $def): void;

        /**
         * Drop an index by its name.
         *
         * @param string $table Table name (unused for some engines but kept for parity).
         * @param string $name Index name.
         */
        public function dropIndex(string $table, string $name): void;

        /**
         * Check if a table exists in the current database/schema.
         *
         * @param string $table Table name.
         * @return bool True if table exists.
         */
        public function tableExists(string $table): bool;

        /**
         * Check if a column exists on the specified table.
         *
         * @param string $table Table name.
         * @param string $column Column name.
         * @return bool True if column exists.
         */
        public function columnExists(string $table, string $column): bool;

        /**
         * Introspect and return a TableDefinition representing the current table.
         *
         * @param string $table Table name.
         * @return TableDefinition Declarative table structure.
         */
        public function getTableDefinition(string $table): TableDefinition;

        /**
         * Execute a raw SQL statement without parameter binding.
         *
         * @param string $sql SQL to execute.
         */
        public function runSql(string $sql): void;

        /**
         * Return a set of capability flags supported by this adapter.
         *
         * @return array<int, string>
         */
        public function getCapabilities(): array;
    }
