<?php
declare(strict_types=1);

use Ishmael\Core\Http\Request;
use PHPUnit\Framework\TestCase;

final class RequestTest extends TestCase
{
    public function testMethodPathQueryHost(): void
    {
        $server = [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/foo/bar?x=1&y=abc',
            'HTTP_HOST' => 'app.test:8080',
        ];
        $get = ['x' => '1', 'y' => 'abc'];
        $post = ['z' => 'ok'];

        $req = new Request('POST', '/foo/bar?x=1&y=abc', $server, $get, $post, [], 'body');

        $this->assertSame('POST', $req->getMethod());
        $this->assertSame('/foo/bar', $req->getPath());
        $this->assertSame('app.test', $req->getHost());
        $this->assertSame(['x' => '1', 'y' => 'abc'], $req->getQueryParams());
        $this->assertSame(['z' => 'ok'], $req->getParsedBody());
        $this->assertSame('body', $req->getRawBody());
    }

    public function testHostFallbacks(): void
    {
        $server = [
            'SERVER_NAME' => 'example.local',
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
        ];
        $req = new Request('GET', '/', $server);
        $this->assertSame('example.local', $req->getHost());

        $server = [
            'SERVER_ADDR' => '127.0.0.1',
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
        ];
        $req = new Request('GET', '/', $server);
        $this->assertSame('127.0.0.1', $req->getHost());
    }
}
