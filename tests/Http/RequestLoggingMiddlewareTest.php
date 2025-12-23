<?php

declare(strict_types=1);

namespace Ishmael\Tests;

use Ishmael\Core\Logger;
use Ishmael\Core\Router;
use Ishmael\Core\Http\Response;
use Ishmael\Core\Http\Middleware\RequestIdMiddleware;
use Ishmael\Core\Http\Middleware\RequestLoggingMiddleware;
use PHPUnit\Framework\TestCase;

final class RequestLoggingMiddlewareTest extends TestCase
{
    private string $logPath;
    protected function setUp(): void
    {
        if (!function_exists('base_path')) {
            require_once __DIR__ . '/../../app/Helpers/helpers.php';
        }
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/rlm';
        if (function_exists('header_remove')) {
            @header_remove();
        }

        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ish_rlm_tests';
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        $this->logPath = $dir . DIRECTORY_SEPARATOR . 'rlm.log';
        Logger::init([
            'default' => 'single',
            'channels' => [
                'single' => [
                    'driver' => 'single',
                    'path' => $this->logPath,
                    'level' => 'debug',
                    'format' => 'json',
                ],
            ],
        ]);
    }

    public function testLogsStartAndFinishWithDurationAndRequestId(): void
    {
        $router = new Router();
        $mw1 = new RequestIdMiddleware();
        $mw2 = new RequestLoggingMiddleware();
        $router->add(['GET'], 'rlm', function ($req, Response $res): Response {

            return Response::text('ok');
        }, [$mw1, $mw2]);
        ob_start();
        $router->dispatch('/rlm');
        ob_end_clean();
        $this->assertFileExists($this->logPath);
        $lines = file($this->logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        $this->assertGreaterThanOrEqual(2, count($lines));
        $foundStart = false;
        $foundFinish = false;
        $rid = null;
        foreach ($lines as $ln) {
            $obj = json_decode($ln, true);
            if (!is_array($obj)) {
                continue;
            }
            if (($obj['msg'] ?? '') === 'HTTP request start') {
                $foundStart = true;
                $rid = $obj['request_id'] ?? $rid;
            }
            if (($obj['msg'] ?? '') === 'HTTP request finish') {
                $foundFinish = true;
                $this->assertArrayHasKey('duration_ms', $obj['context'] ?? []);
                $this->assertIsInt($obj['context']['duration_ms']);
// request_id should be present via processor
                if ($rid !== null) {
                    $this->assertSame($rid, $obj['request_id'] ?? null);
                }
            }
        }
        $this->assertTrue($foundStart, 'Start log not found');
        $this->assertTrue($foundFinish, 'Finish log not found');
// Header added by RequestIdMiddleware
        $headers = \Ishmael\Core\Http\Response::getLastHeaders();
        $this->assertArrayHasKey('X-Request-Id', $headers);
        $this->assertNotSame('', $headers['X-Request-Id']);
    }
}
