<?php
declare(strict_types=1);

namespace Ishmael\Core\Log;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * Thin adapter around Monolog\Logger implementing PSR-3.
 * Allows LoggerManager to treat Monolog as any other channel.
 */
final class MonologChannel implements LoggerInterface
{
    /** @var \Monolog\Logger */
    private \Monolog\Logger $logger;

    public function __construct(\Monolog\Logger $logger)
    {
        $this->logger = $logger;
    }

    public function emergency($message, array $context = []): void { $this->logger->emergency($message, $context); }
    public function alert($message, array $context = []): void { $this->logger->alert($message, $context); }
    public function critical($message, array $context = []): void { $this->logger->critical($message, $context); }
    public function error($message, array $context = []): void { $this->logger->error($message, $context); }
    public function warning($message, array $context = []): void { $this->logger->warning($message, $context); }
    public function notice($message, array $context = []): void { $this->logger->notice($message, $context); }
    public function info($message, array $context = []): void { $this->logger->info($message, $context); }
    public function debug($message, array $context = []): void { $this->logger->debug($message, $context); }

    public function log($level, $message, array $context = []): void
    {
        // Monolog accepts PSR-3 levels as strings as of 1.11+, still valid in v3.
        $this->logger->log($level, $message, $context);
    }
}
