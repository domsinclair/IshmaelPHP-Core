<?php
    declare(strict_types=1);

    namespace Ishmael\Core\DatabaseAdapters;

    use PDO;
    use PDOException;
    use Ishmael\Core\Logger;

    class SQLiteAdapter implements DatabaseAdapterInterface
    {
        public function connect(array $config): PDO
        {
            // Determine database path
            $path = $config['database'] ?? base_path('storage/database.sqlite');

            // Ensure the directory exists
            $dir = dirname($path);
            if (!is_dir($dir)) {
                if (!mkdir($dir, 0777, true) && !is_dir($dir)) {
                    throw new \RuntimeException("Failed to create SQLite directory: {$dir}");
                }
            }

            $dsn = 'sqlite:' . $path;

            try {
                $pdo = new PDO($dsn);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

                Logger::info("SQLite connected: {$path}");

                return $pdo;
            } catch (PDOException $e) {
                Logger::error("SQLite connection failed: " . $e->getMessage());
                throw new \RuntimeException('SQLite connection failed: ' . $e->getMessage());
            }
        }
    }
