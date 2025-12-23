<?php

    declare(strict_types=1);

namespace Ishmael\Core\DatabaseAdapters;

use InvalidArgumentException;

/**
     * Factory for registering and creating database adapter instances by driver name.
     *
     * Adapters must implement DatabaseAdapterInterface and can be registered at runtime.
     */
class DatabaseAdapterFactory
{
    /** @var array<string, class-string<DatabaseAdapterInterface>> */
    private static array $registry = [];
/**
     * Register a new adapter class under a given driver key.
     *
     * @param string $driver Driver identifier (e.g., "mysql", "sqlite", "pgsql").
     * @param class-string<DatabaseAdapterInterface> $class Fully-qualified adapter class name.
     * @throws InvalidArgumentException If the class does not exist or does not implement DatabaseAdapterInterface.
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
     * Get a new adapter instance for a driver.
     *
     * @param string $driver Driver identifier.
     * @return DatabaseAdapterInterface New adapter instance.
     * @throws InvalidArgumentException If no adapter is registered for the driver.
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
     * Register default adapters.
     */
    public static function registerDefaults(): void
    {
        self::register('mysql', \Ishmael\Core\DatabaseAdapters\MySQLAdapter::class);
        self::register('sqlite', \Ishmael\Core\DatabaseAdapters\SQLiteAdapter::class);
        self::register('pgsql', \Ishmael\Core\DatabaseAdapters\PostgresAdapter::class);
        self::register('postgres', \Ishmael\Core\DatabaseAdapters\PostgresAdapter::class);
    }
}
