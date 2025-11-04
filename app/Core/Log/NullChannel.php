<?php
declare(strict_types=1);

namespace Ishmael\Core\Log;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * No-op PSR-3 channel that discards all log messages.
 * Useful for disabling logging in certain environments or for testing.
 */
final class NullChannel implements LoggerInterface
{
    /** @inheritDoc */
    public function emergency($message, array $context = []): void {}
    /** @inheritDoc */
    public function alert($message, array $context = []): void {}
    /** @inheritDoc */
    public function critical($message, array $context = []): void {}
    /** @inheritDoc */
    public function error($message, array $context = []): void {}
    /** @inheritDoc */
    public function warning($message, array $context = []): void {}
    /** @inheritDoc */
    public function notice($message, array $context = []): void {}
    /** @inheritDoc */
    public function info($message, array $context = []): void {}
    /** @inheritDoc */
    public function debug($message, array $context = []): void {}

    /**
     * Accepts any record and ignores it.
     *
     * @param string $level
     * @param string|\Stringable $message
     * @param array<string,mixed> $context
     */
    public function log($level, $message, array $context = []): void {}
}
