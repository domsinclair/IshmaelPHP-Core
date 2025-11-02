<?php
    declare(strict_types=1);

    namespace Ishmael\Core\DatabaseAdapters;

    use InvalidArgumentException;

    class DatabaseAdapterFactory
    {
        /** @var array<string, class-string<DatabaseAdapterInterface>> */
        private static array $registry = [];

        /**
         * Register a new adapter class under a given driver key
         */
        public static function register(string $driver, string $class): void
        {
            if (!class_exists($class)) {
                throw new InvalidArgumentException("Adapter class $class not found.");
            }

            if (!in_array(DatabaseAdapterInterface::class, class_implements($class), true)) {
                throw new InvalidArgumentException("$class must implement DatabaseAdapterInterface.");
            }

            self::$registry[strtolower($driver)] = $class;
        }

        /**
         * Get a new adapter instance for a driver
         */
        public static function create(string $driver): DatabaseAdapterInterface
        {
            $driver = strtolower($driver);

            if (!isset(self::$registry[$driver])) {
                throw new InvalidArgumentException("No database adapter registered for driver [$driver]");
            }

            $class = self::$registry[$driver];
            return new $class();
        }

        /**
         * Register default adapters
         */
        public static function registerDefaults(): void
        {
            self::register('mysql', \Ishmael\Core\DatabaseAdapters\MySQLAdapter::class);
            self::register('sqlite', \Ishmael\Core\DatabaseAdapters\SQLiteAdapter::class);
            self::register('pgsql', \Ishmael\Core\DatabaseAdapters\PostgresAdapter::class);
            self::register('postgres', \Ishmael\Core\DatabaseAdapters\PostgresAdapter::class);
        }
    }
