<?php
    declare(strict_types=1);

    namespace Ishmael\Core;

    use PDO;
    use Ishmael\Core\DatabaseAdapters\DatabaseAdapterFactory;
    use Ishmael\Core\DatabaseAdapters\DatabaseAdapterInterface;

    class Database
    {
        private static ?PDO $connection = null;
        private static ?DatabaseAdapterInterface $adapter = null;

        public static function init(array $config): void
        {
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


        public static function conn(): PDO
        {
            if (!self::$connection) {
                throw new \RuntimeException('Database not initialized');
            }
            return self::$connection;
        }
    }
