<?php

declare(strict_types=1);

namespace Ishmael\Tests\Http;

use Ishmael\Core\Http\Emitter;
use Ishmael\Core\Http\Response;
use PHPUnit\Framework\TestCase;

final class FileResponseTest extends TestCase
{
    public function testDownloadResponseHeaders(): void
    {
        $path = __FILE__;
        $response = Response::download($path, 'test.php');

        $this->assertTrue($response->isFileResponse());
        $this->assertSame($path, $response->getFilePath());

        $headers = $response->getHeaders();
        $this->assertSame('attachment; filename="test.php"', $headers['Content-Disposition']);
        $this->assertStringContainsString('text/x-php', $headers['Content-Type']);
    }

    public function testEmitterStreamsFile(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'ish');
        file_put_contents($tempFile, 'hello world');

        $response = Response::download($tempFile, 'hello.txt');
        $emitter = new Emitter();

        ob_start();
        // We use @ to suppress "headers already sent" warnings in CLI
        @$emitter->emit($response);
        $output = ob_get_clean();

        $this->assertSame('hello world', $output);

        unlink($tempFile);
    }
}
