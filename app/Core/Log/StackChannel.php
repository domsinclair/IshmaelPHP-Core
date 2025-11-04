<?php
declare(strict_types=1);

namespace Ishmael\Core\Log;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * Fan-out logger that forwards each record to multiple underlying channels.
 *
 * Useful when you want to write the same message to several destinations
 * (e.g., a daily file and STDERR) while isolating failures of any single
 * channel from affecting the others.
 */
final class StackChannel implements LoggerInterface
{
    /** @var LoggerInterface[] List of channels to forward to */
    private array $channels;

    /**
     * @param LoggerInterface[] $channels Channels that will receive forwarded records in order.
     */
    public function __construct(array $channels)
    {
        $this->channels = $channels;
    }

    /** @inheritDoc */
    public function emergency($message, array $context = []): void { $this->log(LogLevel::EMERGENCY, $message, $context); }
    /** @inheritDoc */
    public function alert($message, array $context = []): void     { $this->log(LogLevel::ALERT, $message, $context); }
    /** @inheritDoc */
    public function critical($message, array $context = []): void  { $this->log(LogLevel::CRITICAL, $message, $context); }
    /** @inheritDoc */
    public function error($message, array $context = []): void     { $this->log(LogLevel::ERROR, $message, $context); }
    /** @inheritDoc */
    public function warning($message, array $context = []): void   { $this->log(LogLevel::WARNING, $message, $context); }
    /** @inheritDoc */
    public function notice($message, array $context = []): void    { $this->log(LogLevel::NOTICE, $message, $context); }
    /** @inheritDoc */
    public function info($message, array $context = []): void      { $this->log(LogLevel::INFO, $message, $context); }
    /** @inheritDoc */
    public function debug($message, array $context = []): void     { $this->log(LogLevel::DEBUG, $message, $context); }

    /**
     * Forward a record to each configured channel.
     *
     * Exceptions thrown by child channels are swallowed so logging remains
     * best-effort and does not disrupt the application flow.
     *
     * @param string $level PSR-3 level name.
     * @param string|\Stringable $message Log message or object with __toString().
     * @param array<string,mixed> $context Context values for interpolation.
     */
    public function log($level, $message, array $context = []): void
    {
        foreach ($this->channels as $ch) {
            try {
                $ch->log($level, $message, $context);
            } catch (\Throwable $e) {
                // isolate child failures
            }
        }
    }
}
