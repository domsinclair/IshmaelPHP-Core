<?php

    declare(strict_types=1);

namespace Ishmael\Core;

use Ishmael\Core\Log\LoggerManager;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
     * Static facade for application logging built on top of LoggerManager and PSR-3.
     *
     * Provides convenient helpers for initialization and common logging methods.
     */
class Logger
{
    private static ?LoggerInterface $psr = null;
    private static ?LoggerManager $manager = null;
/**
     * Initialize logging.
     *
     * Accepts either a full logging configuration array (with 'channels')
     * or a simple array with a single 'path' for a default single-file channel.
     *
     * @param array<string,mixed> $config Logging configuration.
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

    /**
     * Log a message at an arbitrary level.
     *
     * @param string $level PSR-3 level name.
     * @param string $message Message to log.
     * @param array<string,mixed> $context Context for interpolation.
     */
    public static function log(string $level, string $message, array $context = []): void
    {
        self::logger()->log($level, $message, $context);
    }

    /**
     * Log an informational message.
     *
     * @param string $message
     * @param array<string,mixed> $context
     */
    public static function info(string $message, array $context = []): void
    {
        self::logger()->info($message, $context);
    }

    /**
     * Log an error message.
     *
     * @param string $message
     * @param array<string,mixed> $context
     */
    public static function error(string $message, array $context = []): void
    {
        self::logger()->error($message, $context);
    }

    /**
     * Log a critical error message.
     *
     * @param string $message
     * @param array<string,mixed> $context
     */
    public static function critical(string $message, array $context = []): void
    {
        self::logger()->critical($message, $context);
    }

    /**
     * Get the underlying PSR-3 logger, initializing a default single-file channel if needed.
     */
    private static function logger(): LoggerInterface
    {
        if (!self::$psr) {
// Lazy init to temp file
            self::init(['path' => sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ish_logs' . DIRECTORY_SEPARATOR . 'app.log']);
        }
        return self::$psr;
    }
}
