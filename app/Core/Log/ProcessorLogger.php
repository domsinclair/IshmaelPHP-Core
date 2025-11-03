<?php
declare(strict_types=1);

namespace Ishmael\Core\Log;

use Psr\Log\LoggerInterface;

/**
 * Wraps a logger and applies processors to the context before delegating.
 */
final class ProcessorLogger implements LoggerInterface
{
    /** @var LoggerInterface */
    private LoggerInterface $inner;

    /** @var array<int, callable(array): array> */
    private array $processors;

    /**
     * @param array<int, callable(array): array> $processors
     */
    public function __construct(LoggerInterface $inner, array $processors = [])
    {
        $this->inner = $inner;
        $this->processors = $processors;
    }

    public function emergency($message, array $context = []): void { $this->log('emergency', $message, $context); }
    public function alert($message, array $context = []): void     { $this->log('alert', $message, $context); }
    public function critical($message, array $context = []): void  { $this->log('critical', $message, $context); }
    public function error($message, array $context = []): void     { $this->log('error', $message, $context); }
    public function warning($message, array $context = []): void   { $this->log('warning', $message, $context); }
    public function notice($message, array $context = []): void    { $this->log('notice', $message, $context); }
    public function info($message, array $context = []): void      { $this->log('info', $message, $context); }
    public function debug($message, array $context = []): void     { $this->log('debug', $message, $context); }

    public function log($level, $message, array $context = []): void
    {
        $extras = [];
        foreach ($this->processors as $proc) {
            try {
                $add = $proc($context + $extras);
                if (is_array($add)) {
                    $extras = $extras + $add; // do not overwrite earlier keys unless proc explicitly includes them later
                }
            } catch (\Throwable $e) {
                // Swallow processor failures to avoid breaking logging
            }
        }
        $merged = $context + $extras;
        $this->inner->log($level, $message, $merged);
    }
}
