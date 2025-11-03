<?php
    declare(strict_types=1);

    namespace Ishmael\Core;

    use DateTime;

    use Ishmael\Core\Log\LoggerManager;
    use Psr\Log\LoggerInterface;
    use Psr\Log\LogLevel;

    class Logger
    {
        private static ?LoggerInterface $psr = null;
        private static ?LoggerManager $manager = null;

        /**
         * Back-compat init. Accepts either a single-channel config with 'path' or a full logging config array.
         */
        public static function init(array $config): void
        {
            // Detect if this looks like full logging.php config (has 'channels')
            if (isset($config['channels'])) {
                self::$manager = new LoggerManager($config);
                self::$psr = self::$manager->default();
                // register into service locator for DI access
                if (function_exists('app')) {
                    app([
                        LoggerManager::class => self::$manager,
                        LoggerInterface::class => self::$psr,
                        'logger' => self::$psr,
                    ]);
                }
                return;
            }

            // Fallback: single file path config
            $path = $config['path'] ?? base_path('storage/logs/app.log');
            self::$manager = new LoggerManager([
                'default' => 'single',
                'channels' => [
                    'single' => [
                        'driver' => 'single',
                        'path' => $path,
                        'level' => $config['level'] ?? LogLevel::DEBUG,
                    ],
                ],
            ]);
            self::$psr = self::$manager->default();
            if (function_exists('app')) {
                app([
                    LoggerManager::class => self::$manager,
                    LoggerInterface::class => self::$psr,
                    'logger' => self::$psr,
                ]);
            }
        }

        public static function info(string $message, array $context = []): void
        {
            self::logger()->info($message, $context);
        }

        public static function error(string $message, array $context = []): void
        {
            self::logger()->error($message, $context);
        }

        private static function logger(): LoggerInterface
        {
            if (!self::$psr) {
                // Lazy init to temp file
                self::init(['path' => sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ish_logs' . DIRECTORY_SEPARATOR . 'app.log']);
            }
            return self::$psr;
        }
    }
