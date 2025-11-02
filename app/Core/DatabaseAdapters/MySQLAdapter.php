<?php
    declare(strict_types=1);

    namespace Ishmael\Core\DatabaseAdapters;

    use PDO;
    use PDOException;
    use Ishmael\Core\Logger;

    class MySQLAdapter implements DatabaseAdapterInterface
    {
        public function connect(array $config): PDO
        {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                $config['host'] ?? '127.0.0.1',
                $config['database'] ?? '',
                $config['charset'] ?? 'utf8mb4'
            );

            try {
                $pdo = new PDO(
                    $dsn,
                    $config['username'] ?? 'root',
                    $config['password'] ?? '',
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    ]
                );
                return $pdo;
            } catch (PDOException $e) {
                Logger::error("MySQL connection failed: " . $e->getMessage());
                throw new \RuntimeException('MySQL connection failed');
            }
        }
    }
