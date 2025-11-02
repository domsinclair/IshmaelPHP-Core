<?php
    declare(strict_types=1);

    namespace Ishmael\Core\DatabaseAdapters;

    use PDO;
    use PDOException;
    use Ishmael\Core\Logger;

    class PostgresAdapter implements DatabaseAdapterInterface
    {
        public function connect(array $config): PDO
        {
            $dsn = sprintf(
                'pgsql:host=%s;port=%d;dbname=%s',
                $config['host'] ?? '127.0.0.1',
                $config['port'] ?? 5432,
                $config['database'] ?? ''
            );

            try {
                $pdo = new PDO(
                    $dsn,
                    $config['username'] ?? 'postgres',
                    $config['password'] ?? '',
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    ]
                );

                // Explicitly set charset if supported
                if (isset($config['charset'])) {
                    $pdo->exec("SET NAMES '{$config['charset']}'");
                }

                return $pdo;

            } catch (PDOException $e) {
                Logger::error("PostgreSQL connection failed: " . $e->getMessage());
                throw new \RuntimeException('PostgreSQL connection failed');
            }
        }
    }
