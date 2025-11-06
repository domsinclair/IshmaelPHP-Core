<?php
    declare(strict_types=1);

    namespace Ishmael\Core;

    use DateTimeInterface;
    use PDO;
    use PDOException;
    use PDOStatement;
    use Throwable;
    use Ishmael\Core\Database\Result;
    use Ishmael\Core\DatabaseAdapters\DatabaseAdapterFactory;
    use Ishmael\Core\DatabaseAdapters\DatabaseAdapterInterface;

    class Database
    {
        private static ?PDO $connection = null;
        private static ?DatabaseAdapterInterface $adapter = null;

        /**
         * Initialize the default database connection and adapter.
         *
         * In test mode (ISH_TESTING=1), this method will always reset any existing
         * connection/adapter first so each test can call init() safely without
         * leaking state across tests.
         *
         * @param array<string,mixed> $config Database configuration array with keys: default, connections.
         */
        public static function init(array $config): void
        {
            // In tests, always reset before (re)initializing to ensure isolation
            if ((($_SERVER['ISH_TESTING'] ?? null) === '1') && (self::$connection !== null || self::$adapter !== null)) {
                self::reset();
            }

            if (self::$connection) {
                return;
            }

            $defaultConnectionName = $config['default'] ?? 'mysql';
            $connections = $config['connections'] ?? [];

            if (!isset($connections[$defaultConnectionName])) {
                throw new \RuntimeException("Database connection '{$defaultConnectionName}' not found.");
            }

            $connectionConfig = $connections[$defaultConnectionName];
            $driver = strtolower($connectionConfig['driver'] ?? 'mysql');

            // Register all known adapters (core + any custom)
            DatabaseAdapters\DatabaseAdapterFactory::registerDefaults();

            // Get adapter instance dynamically
            self::$adapter = DatabaseAdapters\DatabaseAdapterFactory::create($driver);

            // Connect
            self::$connection = self::$adapter->connect($connectionConfig);
        }

        /**
         * Get the underlying PDO connection. Useful for low-level operations.
         */
        public static function conn(): PDO
        {
            if (!self::$connection) {
                throw new \RuntimeException('Database not initialized');
            }
            return self::$connection;
        }

        /**
         * Get the active DatabaseAdapterInterface.
         */
        public static function adapter(): DatabaseAdapterInterface
        {
            if (!self::$adapter) {
                throw new \RuntimeException('Database not initialized');
            }
            return self::$adapter;
        }

        /**
         * Reset the static Database state, disconnecting any active adapter and clearing
         * the stored PDO connection. Safe to call multiple times. Intended primarily for tests.
         */
        public static function reset(): void
        {
            try {
                if (self::$adapter !== null) {
                    self::$adapter->disconnect();
                }
            } catch (\Throwable $e) {
                // ignore during reset
            }
            self::$connection = null;
            self::$adapter = null;
        }

        /**
         * Run a callable inside a transaction boundary with automatic commit/rollback.
         *
         * Nested transactions: if a transaction is already active on the adapter, the
         * callable is executed directly without starting a new transaction, and no
         * commit/rollback is performed at this level. This defers to adapter/driver semantics
         * for nested transactions (savepoints are not emulated here).
         *
         * @template T
         * @param callable():T $fn Callback to execute inside the transaction.
         * @return T Return value of the callback is returned to the caller.
         * @throws Throwable Re-throws any exception thrown by the callback after rolling back.
         */
        public static function transaction(callable $fn)
        {
            $adapter = self::adapter();

            if ($adapter->inTransaction()) {
                // Nested: just run the callback; outer scope controls commit/rollback.
                return $fn();
            }

            $adapter->beginTransaction();
            try {
                $result = $fn();
                $adapter->commit();
                return $result;
            } catch (Throwable $e) {
                // Best-effort rollback; if rollback throws, prefer original exception.
                try { $adapter->rollBack(); } catch (Throwable $ignored) {}
                throw $e;
            }
        }

        /**
         * Retry a transaction in case of transient conflicts (e.g., deadlocks or serialization failures).
         *
         * @template T
         * @param int $attempts Maximum number of attempts (>=1).
         * @param int $sleepMs Sleep between attempts in milliseconds (>=0).
         * @param callable():T $fn Transactional callback.
         * @return T The successful callback result.
         * @throws Throwable After exhausting attempts or on non-retryable error.
         */
        public static function retryTransaction(int $attempts, int $sleepMs, callable $fn)
        {
            if ($attempts < 1) { $attempts = 1; }
            if ($sleepMs < 0) { $sleepMs = 0; }

            $last = null;
            for ($i = 0; $i < $attempts; $i++) {
                try {
                    return self::transaction($fn);
                } catch (Throwable $e) {
                    $last = $e;
                    if (!self::isTransientTransactionError($e) || $i === $attempts - 1) {
                        throw $e;
                    }
                    if ($sleepMs > 0) {
                        usleep($sleepMs * 1000);
                    }
                }
            }
            // Should not reach here; rethrow last if somehow did
            if ($last) { throw $last; }
            return self::transaction($fn);
        }

        /**
         * Prepare a SQL statement using the underlying PDO connection.
         */
        public static function prepare(string $sql): PDOStatement
        {
            return self::conn()->prepare($sql);
        }

        /**
         * Execute a SELECT statement and return a Result wrapper. Accepts named or positional params.
         *
         * @param array<int|string,mixed> $params
         */
        public static function query(string $sql, array $params = []): Result
        {
            // Normalize values for safe binding across drivers
            $norm = self::normalizeParams($params);
            return self::adapter()->query($sql, $norm);
        }

        /**
         * Execute a DML statement (INSERT/UPDATE/DELETE) and return affected row count.
         *
         * @param array<int|string,mixed> $params
         */
        public static function execute(string $sql, array $params = []): int
        {
            $norm = self::normalizeParams($params);
            return self::adapter()->execute($sql, $norm);
        }

        /**
         * Normalize parameter values for binding. Converts DateTimeInterface to ISO8601 strings,
         * booleans to integers, and leaves other scalar values unchanged. Nulls are preserved.
         *
         * @param array<int|string,mixed> $params
         * @return array<int|string,mixed>
         */
        public static function normalizeParams(array $params): array
        {
            $out = [];
            foreach ($params as $k => $v) {
                $out[$k] = self::normalizeParamValue($v);
            }
            return $out;
        }

        /**
         * Normalize a single parameter value.
         *
         * @param mixed $value
         * @return mixed
         */
        private static function normalizeParamValue(mixed $value): mixed
        {
            if ($value === null) { return null; }
            if ($value instanceof DateTimeInterface) { return $value->format('c'); }
            if (is_bool($value)) { return $value ? 1 : 0; }
            if (is_object($value) && method_exists($value, '__toString')) { return (string)$value; }
            return $value;
        }

        /**
         * Heuristic detection of transient, retryable transaction errors.
         */
        private static function isTransientTransactionError(Throwable $e): bool
        {
            // Unwrap PDOException or nested exceptions
            $pdoe = $e instanceof PDOException ? $e : ( ($e->getPrevious() instanceof PDOException) ? $e->getPrevious() : null );
            if ($pdoe instanceof PDOException) {
                $code = (string)$pdoe->getCode();
                $msg = strtolower($pdoe->getMessage());
                // SQLSTATE codes: 40001 (serialization failure), 40P01 (deadlock PG)
                if ($code === '40001' || $code === '40P01') { return true; }
                // MySQL/InnoDB deadlock/timeouts specific codes
                if ($code === '1213' || $code === '1205') { return true; }
                if (str_contains($msg, 'deadlock') || str_contains($msg, 'serialization failure')) { return true; }
            }
            return false;
        }
    }
