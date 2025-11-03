<?php
declare(strict_types=1);

namespace Ishmael\Core\Log;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

final class DailyRotatingFileChannel implements LoggerInterface
{
    use BaseLoggerTrait;

    private string $basePath;
    private int $days;
    private FormatterInterface $formatter;
    private bool $retentionApplied = false;

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
    }

    public function emergency($message, array $context = []): void { $this->log(LogLevel::EMERGENCY, $message, $context); }
    public function alert($message, array $context = []): void     { $this->log(LogLevel::ALERT, $message, $context); }
    public function critical($message, array $context = []): void  { $this->log(LogLevel::CRITICAL, $message, $context); }
    public function error($message, array $context = []): void     { $this->log(LogLevel::ERROR, $message, $context); }
    public function warning($message, array $context = []): void   { $this->log(LogLevel::WARNING, $message, $context); }
    public function notice($message, array $context = []): void    { $this->log(LogLevel::NOTICE, $message, $context); }
    public function info($message, array $context = []): void      { $this->log(LogLevel::INFO, $message, $context); }
    public function debug($message, array $context = []): void     { $this->log(LogLevel::DEBUG, $message, $context); }

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

    private function write(string $path, string $line): void
    {
        $fh = @fopen($path, 'ab');
        if (!$fh) {
            return;
        }
        try {
            @flock($fh, LOCK_EX);
            @fwrite($fh, $line);
            @fflush($fh);
        } finally {
            @flock($fh, LOCK_UN);
            @fclose($fh);
        }
    }

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
