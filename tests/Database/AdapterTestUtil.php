<?php
declare(strict_types=1);

namespace Tests\Database;

use Ishmael\Core\DatabaseAdapters\DatabaseAdapterFactory;
use Ishmael\Core\DatabaseAdapters\DatabaseAdapterInterface;

final class AdapterTestUtil
{
    /** Create a SQLite in-memory adapter by default. */
    public static function sqliteAdapter(): DatabaseAdapterInterface
    {
        DatabaseAdapterFactory::registerDefaults();
        $adapter = DatabaseAdapterFactory::create('sqlite');
        $adapter->connect(['database' => ':memory:']);
        return $adapter;
    }

    /** Create an adapter from a DSN, e.g., mysql://user:pass@host:3306/dbname */
    public static function fromDsn(string $dsnEnvVar): ?DatabaseAdapterInterface
    {
        $dsn = getenv($dsnEnvVar);
        if (!$dsn) {
            return null;
        }
        $parts = parse_url($dsn);
        if ($parts === false || !isset($parts['scheme'])) {
            return null;
        }
        $driver = $parts['scheme'];
        $config = [];
        if ($driver === 'sqlite') {
            $config['database'] = ($parts['path'] ?? ':memory:') ?: ':memory:';
        } else {
            $config = [
                'host' => $parts['host'] ?? '127.0.0.1',
                'port' => isset($parts['port']) ? (int)$parts['port'] : null,
                'database' => isset($parts['path']) ? ltrim((string)$parts['path'], '/') : null,
                'username' => $parts['user'] ?? null,
                'password' => $parts['pass'] ?? null,
                'charset' => 'utf8mb4',
            ];
        }
        DatabaseAdapterFactory::registerDefaults();
        $adapter = DatabaseAdapterFactory::create($driver);
        $adapter->connect(array_filter($config, static fn($v) => $v !== null && $v !== ''));
        return $adapter;
    }
}
