<?php
declare(strict_types=1);

namespace Ishmael\Core\Support;

use Ishmael\Core\Logger as CoreLogger;
use Psr\Log\LoggerInterface;

/**
 * Static logging facade for newcomer ergonomics.
 *
 * Recommended for quick starts and simple apps:
 *   use Ishmael\Core\Support\Log;
 *   Log::info('Hello');
 *
 * Framework internals and advanced apps should prefer DI by type-hinting
 * Psr\Log\LoggerInterface in constructors or handlers. This facade simply
 * resolves the PSR logger from the lightweight service locator when available,
 * and otherwise falls back to CoreLogger's static entrypoints.
 */
final class Log
{
    public static function emergency(string $message, array $context = []): void
    {
        $logger = self::resolve();
        if ($logger) { $logger->emergency($message, $context); return; }
        CoreLogger::log('emergency', $message, $context);
    }

    public static function alert(string $message, array $context = []): void
    {
        $logger = self::resolve();
        if ($logger) { $logger->alert($message, $context); return; }
        CoreLogger::log('alert', $message, $context);
    }

    public static function critical(string $message, array $context = []): void
    {
        $logger = self::resolve();
        if ($logger) { $logger->critical($message, $context); return; }
        CoreLogger::log('critical', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        $logger = self::resolve();
        if ($logger) { $logger->error($message, $context); return; }
        CoreLogger::error($message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        $logger = self::resolve();
        if ($logger) { $logger->warning($message, $context); return; }
        CoreLogger::log('warning', $message, $context);
    }

    public static function notice(string $message, array $context = []): void
    {
        $logger = self::resolve();
        if ($logger) { $logger->notice($message, $context); return; }
        CoreLogger::log('notice', $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        $logger = self::resolve();
        if ($logger) { $logger->info($message, $context); return; }
        CoreLogger::info($message, $context);
    }

    public static function debug(string $message, array $context = []): void
    {
        $logger = self::resolve();
        if ($logger) { $logger->debug($message, $context); return; }
        CoreLogger::log('debug', $message, $context);
    }

    public static function log(string $level, string $message, array $context = []): void
    {
        $logger = self::resolve();
        if ($logger) { $logger->log($level, $message, $context); return; }
        CoreLogger::log($level, $message, $context);
    }

    private static function resolve(): ?LoggerInterface
    {
        if (function_exists('app')) {
            $logger = \app(LoggerInterface::class);
            if ($logger instanceof LoggerInterface) {
                return $logger;
            }
            $logger = \app('logger');
            if ($logger instanceof LoggerInterface) {
                return $logger;
            }
        }
        return null;
    }
}
