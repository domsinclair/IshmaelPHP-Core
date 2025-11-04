<?php
declare(strict_types=1);

namespace Ishmael\Core\Log;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * File channel that rotates logs daily and enforces a retention policy.
 *
 * Appends newline-terminated formatted records to files named with a
 * -YYYY-MM-DD suffix derived from a base path (e.g., app.log → app-2025-11-04.log).
 */
final class DailyRotatingFileChannel implements LoggerInterface
{
    use BaseLoggerTrait;

    private string $basePath;
    private int $days;
    private FormatterInterface $formatter;
    private bool $retentionApplied = false;
    /** @var resource|null File handle for the current day file */
    private $handle = null;
    private ?string $currentFile = null;

    /**
     * @param string $basePath Base path used to build the dated filename.
     * @param int $days Number of days to retain old files (min 1).
     * @param string $minLevel Minimum level name for records to be written.
     * @param FormatterInterface|null $formatter Formatter converting records to lines.
     */
    public function __construct(string $basePath, int $days = 7, string $minLevel = LogLevel::DEBUG, ?FormatterInterface $formatter = null)
    {
        $this->basePath = $basePath;
        $this->days = max(1, $days);
        $this->setMinLevel($minLevel);
        $this->formatter = $formatter ?? new JsonLinesFormatter();
        $dir = \dirname($this->basePath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
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
     * Write a record to the current day's file and apply retention once per run.
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
        $path = $this->currentPath();
        if (!$this->retentionApplied) {
            $this->applyRetention();
            $this->retentionApplied = true;
        }
        $this->write($path, $line);
    }

    /** Compute the dated log file path for the current day. */
    private function currentPath(): string
    {
        $dir = \dirname($this->basePath);
        $filename = basename($this->basePath);
        $dotPos = strrpos($filename, '.');
        $date = date('Y-m-d');
        if ($dotPos !== false) {
            $name = substr($filename, 0, $dotPos);
            $ext = substr($filename, $dotPos); // includes dot
            $new = $name . '-' . $date . $ext;
        } else {
            $new = $filename . '-' . $date;
        }
        return $dir . DIRECTORY_SEPARATOR . $new;
    }

    /** Get or open the file handle for the given path, rotating when the day changes. */
    private function getHandle(string $path)
    {
        // Rotate handle if date changed
        if (!is_resource($this->handle) || $this->currentFile !== $path) {
            $this->closeHandle();
            $this->handle = @fopen($path, 'ab');
            $this->currentFile = $path;
        }
        return $this->handle;
    }

    /** Close the current file handle if open. */
    private function closeHandle(): void
    {
        if (is_resource($this->handle)) {
            @fclose($this->handle);
            $this->handle = null;
            $this->currentFile = null;
        }
    }

    /** Write a single line to the current day's file with basic locking. */
    private function write(string $path, string $line): void
    {
        $fh = $this->getHandle($path);
        if (!$fh) {
            return;
        }
        @flock($fh, LOCK_EX);
        @fwrite($fh, $line);
        @fflush($fh);
        @flock($fh, LOCK_UN);
    }

    /**
     * Remove outdated rotated files older than the retention window.
     */
    private function applyRetention(): void
    {
        $dir = \dirname($this->basePath);
        $filename = basename($this->basePath);
        $dotPos = strrpos($filename, '.');
        $prefix = $dotPos !== false ? substr($filename, 0, $dotPos) : $filename;
        $suffix = $dotPos !== false ? substr($filename, $dotPos) : '';
        $patternStart = $prefix . '-';
        $now = time();
        $keepSeconds = $this->days * 86400;
        $dh = @opendir($dir);
        if (!$dh) {
            return;
        }
        try {
            while (($file = readdir($dh)) !== false) {
                if (!is_string($file)) { continue; }
                if (str_starts_with($file, $patternStart) && str_ends_with($file, $suffix)) {
                    // extract date segment YYYY-MM-DD
                    $datePart = substr($file, strlen($patternStart), 10);
                    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $datePart) === 1) {
                        $ts = strtotime($datePart . ' 00:00:00');
                        if ($ts !== false && ($now - $ts) > $keepSeconds) {
                            @unlink($dir . DIRECTORY_SEPARATOR . $file);
                        }
                    }
                }
            }
        } finally {
            @closedir($dh);
        }
    }
}
