<?php
    declare(strict_types=1);

    namespace Ishmael\Core;

    use DateTime;

    class Logger
    {
        private static ?string $logPath = null;

        public static function init(array $config): void
        {
            self::$logPath = $config['path'] ?? base_path('storage/logs/app.log');
            $dir = dirname(self::$logPath);
            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }
        }

        public static function info(string $message): void
        {
            self::write('INFO', $message);
        }

        public static function error(string $message): void
        {
            self::write('ERROR', $message);
        }

        private static function write(string $level, string $message): void
        {
            // Lazy-initialize to a safe default if not set (e.g., during tests)
            if (self::$logPath === null) {
                $defaultDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ish_logs';
                if (!is_dir($defaultDir)) {
                    @mkdir($defaultDir, 0777, true);
                }
                self::$logPath = $defaultDir . DIRECTORY_SEPARATOR . 'app.log';
            }

            $dir = dirname(self::$logPath);
            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }

            $date = (new DateTime())->format('Y-m-d H:i:s');
            file_put_contents(
                self::$logPath,
                "[{$date}] {$level}: {$message}\n",
                FILE_APPEND
            );
        }
    }
