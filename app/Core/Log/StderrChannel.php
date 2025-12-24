<?php

declare(strict_types=1);

namespace Ishmael\Core\Log;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Stringable;

/**
 * PSR-3 logging channel that writes formatted log lines to STDERR.
 *
 * Useful for containerized environments where logs are collected from stdout/stderr.
 */
final class StderrChannel implements LoggerInterface
{
    use BaseLoggerTrait;


    private FormatterInterface $formatter;
/** @var resource|null Stream handle to php://stderr */
    private $stream = null;
/**
     * Create a STDERR logging channel.
     *
     * @param string $minLevel Minimum level name at or above which messages are written.
     * @param FormatterInterface|null $formatter Formatter responsible for converting records to a line.
     */
    public function __construct(string $minLevel = LogLevel::DEBUG, ?FormatterInterface $formatter = null)
    {
        $this->setMinLevel($minLevel);
        $this->formatter = $formatter ?? new JsonLinesFormatter();
    }

    /** @inheritDoc */
    public function emergency($message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }
    /** @inheritDoc */
    public function alert($message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }
    /** @inheritDoc */
    public function critical($message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }
    /** @inheritDoc */
    public function error($message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }
    /** @inheritDoc */
    public function warning($message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }
    /** @inheritDoc */
    public function notice($message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }
    /** @inheritDoc */
    public function info($message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }
    /** @inheritDoc */
    public function debug($message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    /**
     * Write a log record to STDERR if above the configured threshold.
     *
     * @param string $level PSR-3 level name.
     * @param string|Stringable $message Log message or object with __toString().
     * @param array<string,mixed> $context Context values for interpolation.
     */
    public function log($level, $message, array $context = []): void
    {
        $lvl = strtolower((string)$level);
        if (!$this->shouldLog($lvl)) {
            return;
        }
        $record = $this->buildRecord($lvl, (string)$message, $context);
        $line = $this->formatter->format($record);
        $this->write($line);
    }

    /**
     * Lazily open php://stderr and write a single formatted line.
     */
    private function write(string $line): void
    {
        if (!is_resource($this->stream)) {
            $this->stream = @fopen('php://stderr', 'wb');
            if (!is_resource($this->stream)) {
                return;
            }
        }
        @fwrite($this->stream, $line);
        @fflush($this->stream);
    }
}
