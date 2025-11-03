<?php
declare(strict_types=1);

use Ishmael\Core\Logger;
use Ishmael\Core\Router;
use Ishmael\Core\Http\Response;
use Ishmael\Core\Http\Middleware\RequestIdMiddleware;
use PHPUnit\Framework\TestCase;

final class RequestIdMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        // Ensure helpers loaded
        if (!function_exists('base_path')) {
            require_once __DIR__ . '/../../app/Helpers/helpers.php';
        }
        // Reset superglobals
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/rid';
        // Clear headers emitted in previous tests if any
        if (function_exists('header_remove')) {
            @header_remove();
        }
        // Initialize logger to a temp file per test run
        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ish_rid_tests';
        if (!is_dir($dir)) { @mkdir($dir, 0777, true); }
        $this->logPath = $dir . DIRECTORY_SEPARATOR . 'rid.log';
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

    private string $logPath;

    public function testIncomingHeaderPropagatesToResponseAndLogs(): void
    {
        $incoming = 'test-req-id-1234567890';
        $_SERVER['HTTP_X_REQUEST_ID'] = $incoming;

        $router = new Router();
        $mw = new RequestIdMiddleware();
        $router->add(['GET'], 'rid', function($req, Response $res): Response {
            // Produce a log entry
            Logger::info('hit', ['foo' => 'bar']);
            return Response::text('ok');
        }, [$mw]);

        ob_start();
        $router->dispatch('/rid');
        ob_end_clean();

        // Assert header set on response
        $headers = \Ishmael\Core\Http\Response::getLastHeaders();
        $this->assertArrayHasKey('X-Request-Id', $headers);
        $this->assertSame($incoming, $headers['X-Request-Id']);

        // Assert log contains request_id
        $this->assertFileExists($this->logPath);
        $lines = file($this->logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        $this->assertNotEmpty($lines);
        $last = json_decode($lines[count($lines)-1], true);
        $this->assertIsArray($last);
        $this->assertSame('hit', $last['msg'] ?? null);
        $this->assertSame($incoming, $last['request_id'] ?? null);
    }

    public function testGeneratesIdWhenMissing(): void
    {
        unset($_SERVER['HTTP_X_REQUEST_ID']);

        $router = new Router();
        $mw = new RequestIdMiddleware();
        $router->add(['GET'], 'rid', function($req, Response $res): Response {
            Logger::info('hit2');
            return Response::text('ok2');
        }, [$mw]);

        ob_start();
        $router->dispatch('/rid');
        ob_end_clean();

        $headers = \Ishmael\Core\Http\Response::getLastHeaders();
        $ridHeader = $headers['X-Request-Id'] ?? null;
        $this->assertIsString($ridHeader);
        $this->assertNotSame('', $ridHeader);

        // Log should include generated request_id
        $this->assertFileExists($this->logPath);
        $lines = file($this->logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        $this->assertNotEmpty($lines);
        $found = false;
        foreach ($lines as $ln) {
            $obj = json_decode($ln, true);
            if (($obj['msg'] ?? null) === 'hit2') {
                $this->assertIsString($obj['request_id'] ?? null);
                $this->assertNotSame('', $obj['request_id']);
                $found = true;
            }
        }
        $this->assertTrue($found, 'Log with hit2 not found including request_id');
    }
}
