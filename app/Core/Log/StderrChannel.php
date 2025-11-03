<?php
declare(strict_types=1);

namespace Ishmael\Core\Log;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

final class StderrChannel implements LoggerInterface
{
    use BaseLoggerTrait;

    private FormatterInterface $formatter;

    /** @var resource|null */
    private $stream = null;

    public function __construct(string $minLevel = LogLevel::DEBUG, ?FormatterInterface $formatter = null)
    {
        $this->setMinLevel($minLevel);
        $this->formatter = $formatter ?? new JsonLinesFormatter();
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
        $this->write($line);
    }

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
