<?php
declare(strict_types=1);

namespace Ishmael\Core\Log;

use Ishmael\Core\Log\Processor\RequestIdProcessor;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * LoggerManager resolves log channels from configuration and returns PSR-3 loggers.
 * Phase 2: supports drivers single, daily, stderr, null, and stack.
 */
final class LoggerManager
{
    /** @var array<string,mixed> */
    private array $config;

    /** @var array<string,LoggerInterface> */
    private array $instances = [];

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function default(): LoggerInterface
    {
        $name = (string)($this->config['default'] ?? 'single');
        return $this->channel($name);
    }

    public function channel(string $name): LoggerInterface
    {
        if (isset($this->instances[$name])) {
            return $this->instances[$name];
        }
        $channels = $this->config['channels'] ?? [];
        $cfg = $channels[$name] ?? null;
        if (!is_array($cfg)) {
            // Fallback to single file channel in temp directory
            $logger = new SingleFileChannel(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ish_logs' . DIRECTORY_SEPARATOR . $name . '.log');
            return $this->instances[$name] = $logger;
        }
        $driver = (string)($cfg['driver'] ?? 'single');
        $level = strtolower((string)($cfg['level'] ?? LogLevel::DEBUG));
        $format = strtolower((string)($cfg['format'] ?? 'json'));
        $formatter = $this->makeFormatter($format);
        switch ($driver) {
            case 'stack':
                $channelsList = $cfg['channels'] ?? ['single'];
                $loggers = array_map(fn($n) => $this->channel((string)$n), $channelsList);
                $logger = new StackChannel($loggers);
                break;
            case 'daily':
                $path = (string)($cfg['path'] ?? (sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ish_logs' . DIRECTORY_SEPARATOR . 'app.log'));
                $days = (int)($cfg['days'] ?? 7);
                $logger = new DailyRotatingFileChannel($path, $days, $level, $formatter);
                break;
            case 'stderr':
                $logger = new StderrChannel($level, $formatter);
                break;
            case 'null':
                $logger = new NullChannel();
                break;
            case 'single':
            default:
                $path = (string)($cfg['path'] ?? (sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ish_logs' . DIRECTORY_SEPARATOR . 'app.log'));
                $logger = new SingleFileChannel($path, $level, $formatter);
                break;
        }
        // Wrap with processors so every channel includes global context
        $processors = [new RequestIdProcessor()];
        $logger = new ProcessorLogger($logger, $processors);
        return $this->instances[$name] = $logger;
    }

    private function makeFormatter(string $format): FormatterInterface
    {
        return $format === 'line' ? new LineFormatter() : new JsonLinesFormatter();
    }
}
