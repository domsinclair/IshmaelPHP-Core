<?php
declare(strict_types=1);

namespace Ishmael\Core\Log;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * PSR-3 channel that appends newline-terminated formatted records to a single file.
 *
 * Ensures the target directory exists and keeps the file handle open for the
 * duration of the request to minimize overhead. Uses an advisory lock while
 * writing to avoid interleaving under concurrent writes within the same process.
 */
final class SingleFileChannel implements LoggerInterface
{
    use BaseLoggerTrait;

    /** Absolute path to the log file */
    private string $path;
    private FormatterInterface $formatter;
    /** @var resource|null File handle for the log file */
    private $handle = null;

    /**
     * @param string $path Absolute path to the destination log file.
     * @param string $minLevel Minimum level name for records to be written.
     * @param FormatterInterface|null $formatter Formatter converting records to lines.
     */
    public function __construct(string $path, string $minLevel = LogLevel::DEBUG, ?FormatterInterface $formatter = null)
    {
        $this->path = $path;
        $this->setMinLevel($minLevel);
        $this->formatter = $formatter ?? new JsonLinesFormatter();
        $dir = \dirname($this->path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        // Ensure handle closed at end of request
        register_shutdown_function(function () {
            $this->closeHandle();
        });
    }

    public function __destruct()
    {
        $this->closeHandle();
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
     * Write a record if it meets the configured threshold.
     *
     * @param string $level PSR-3 level name.
     * @param string|\Stringable $message Log message or object with __toString().
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

    /** Get or open the file handle used for writing. */
    private function getHandle()
    {
        if (!is_resource($this->handle)) {
            $this->handle = @fopen($this->path, 'ab');
        }
        return $this->handle;
    }

    /** Close the file handle if open. */
    private function closeHandle(): void
    {
        if (is_resource($this->handle)) {
            @fclose($this->handle);
            $this->handle = null;
        }
    }

    /** Write a single formatted line to the file with basic locking. */
    private function write(string $line): void
    {
        $fh = $this->getHandle();
        if (!$fh) {
            return;
        }
        @flock($fh, LOCK_EX);
        @fwrite($fh, $line);
        @fflush($fh);
        @flock($fh, LOCK_UN);
    }
}
