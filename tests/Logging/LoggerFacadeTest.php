<?php

declare(strict_types=1);

namespace Ishmael\Tests;

use Ishmael\Core\Logger;
use PHPUnit\Framework\TestCase;

final class LoggerFacadeTest extends TestCase
{
    public function testWritesToConfiguredPath(): void
    {
        // Explicitly configure logger to a temp file to avoid interference from test bootstrap
        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ish_logs_facade_tests';
        @mkdir($dir, 0777, true);
        $file = $dir . DIRECTORY_SEPARATOR . 'configured.log';
        if (is_file($file)) {
            @unlink($file);
        }

        Logger::init(['path' => $file, 'level' => 'debug']);
        Logger::info('facade configured path test', ['case' => 'configured']);
        $this->assertFileExists($file, 'Expected configured log file to be created');
        $contents = file_get_contents($file) ?: '';
        $this->assertStringContainsString('facade configured path test', $contents);
    }

    public function testExplicitInitHonorsCustomPath(): void
    {
        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ish_logs_custom_tests';
        @mkdir($dir, 0777, true);
        $file = $dir . DIRECTORY_SEPARATOR . 'custom.log';
        if (is_file($file)) {
            @unlink($file);
        }

        Logger::init(['path' => $file, 'level' => 'info']);
        Logger::error('explicit path test', ['ok' => true]);
        $this->assertFileExists($file);
        $body = file_get_contents($file) ?: '';
        $this->assertStringContainsString('explicit path test', $body);
    }
}
