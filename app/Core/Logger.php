<?php
    declare(strict_types=1);

    namespace Ishmael\Core;

    use DateTime;

    class Logger
    {
        private static string $logPath;

        public static function init(array $config): void
        {
            self::$logPath = $config['path'] ?? base_path('storage/logs/app.log');
            if (!is_dir(dirname(self::$logPath))) {
                mkdir(dirname(self::$logPath), 0777, true);
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
            $date = (new DateTime())->format('Y-m-d H:i:s');
            file_put_contents(
                self::$logPath,
                "[{$date}] {$level}: {$message}\n",
                FILE_APPEND
            );
        }
    }
