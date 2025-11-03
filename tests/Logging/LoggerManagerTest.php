<?php
declare(strict_types=1);

use Ishmael\Core\Log\LoggerManager;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;

final class LoggerManagerTest extends TestCase
{
    public function testManagerResolvesDefaultPsrLogger(): void
    {
        // Ensure helper functions are loaded
        if (!function_exists('base_path')) {
            require_once __DIR__ . '/../../app/Helpers/helpers.php';
        }
        // Build a temp path for test isolation
        $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ish_test_logs';
        if (!is_dir($tempDir)) { @mkdir($tempDir, 0777, true); }
        $logPath = $tempDir . DIRECTORY_SEPARATOR . 'test.log';

        $config = [
            'default' => 'single',
            'channels' => [
                'single' => [
                    'driver' => 'single',
                    'path'   => $logPath,
                    'level'  => 'debug',
                ],
            ],
        ];

        $manager = new LoggerManager($config);
        $logger = $manager->default();

        $this->assertInstanceOf(LoggerInterface::class, $logger);

        // Attempt to log
        $logger->info('Unit test line', ['k' => 'v']);

        $this->assertFileExists($logPath);
        $contents = file_get_contents($logPath);
        $this->assertIsString($contents);
        $this->assertStringContainsString('Unit test line', $contents);
    }

    public function testAppHelperProvidesLoggerWhenRegistered(): void
    {
        if (!function_exists('app')) {
            require_once __DIR__ . '/../../app/Helpers/helpers.php';
        }

        $config = [
            'default' => 'single',
            'channels' => [
                'single' => [
                    'driver' => 'single',
                    'path'   => sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ish_test_logs' . DIRECTORY_SEPARATOR . 'helper.log',
                    'level'  => 'info',
                ],
            ],
        ];

        $manager = new LoggerManager($config);
        $logger = $manager->default();
        app([
            LoggerManager::class => $manager,
            LoggerInterface::class => $logger,
            'logger' => $logger,
        ]);

        $resolved = app('logger');
        $this->assertInstanceOf(LoggerInterface::class, $resolved);
    }
}
