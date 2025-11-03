<?php
declare(strict_types=1);

namespace Ishmael\Core\Log;

use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * Minimal PSR-3 compliant logger that appends to a single file.
 * Intended as a simple default for Phase 1; formatting kept plain for now.
 */
final class SimpleFileLogger implements LoggerInterface
{
    private string $path;
    private string $level;

    /** @var array<string,int> */
    private const LEVEL_MAP = [
        LogLevel::EMERGENCY => 0,
        LogLevel::ALERT     => 1,
        LogLevel::CRITICAL  => 2,
        LogLevel::ERROR     => 3,
        LogLevel::WARNING   => 4,
        LogLevel::NOTICE    => 5,
        LogLevel::INFO      => 6,
        LogLevel::DEBUG     => 7,
    ];

    public function __construct(string $path, string $minLevel = LogLevel::DEBUG)
    {
        $this->path  = $path;
        $this->level = strtolower($minLevel);
        $dir = \dirname($this->path);
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
        $level = strtolower((string)$level);
        if (!isset(self::LEVEL_MAP[$level])) {
            // Unknown level; ignore
            return;
        }
        if (self::LEVEL_MAP[$level] > self::LEVEL_MAP[$this->level]) {
            return; // below threshold
        }

        $line = $this->format((string)$message, $level, $context);
        @file_put_contents($this->path, $line, FILE_APPEND);
    }

    private function format(string $message, string $level, array $context): string
    {
        $message = $this->interpolate($message, $context);
        $date = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        return sprintf("[%s] %s: %s\n", $date, strtoupper($level), $message);
    }

    private function interpolate(string $message, array $context): string
    {
        if (strpos($message, '{') === false) {
            return $message;
        }
        $replacements = [];
        foreach ($context as $key => $val) {
            if (is_null($val) || is_scalar($val) || (is_object($val) && method_exists($val, '__toString'))) {
                $replacements['{'.$key.'}'] = (string)$val;
            } elseif (is_object($val)) {
                $replacements['{'.$key.'}'] = '[object '.get_class($val).']';
            } else {
                $replacements['{'.$key.'}'] = json_encode($val, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }
        }
        return strtr($message, $replacements);
    }
}
