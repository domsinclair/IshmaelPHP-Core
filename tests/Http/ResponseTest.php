<?php
declare(strict_types=1);

use Ishmael\Core\Http\Response;
use PHPUnit\Framework\TestCase;

final class ResponseTest extends TestCase
{
    public function testBasicStatusHeadersBody(): void
    {
        $res = new Response('hello', 201, ['X-Foo' => 'Bar']);
        $this->assertSame(201, $res->getStatusCode());
        $this->assertSame('hello', $res->getBody());
        $this->assertSame(['X-Foo' => 'Bar'], $res->getHeaders());

        $res->setStatusCode(202)->header('X-Bar', 'Baz')->setBody('ok');
        $this->assertSame(202, $res->getStatusCode());
        $this->assertSame('ok', $res->getBody());
        $this->assertSame('Baz', $res->getHeaders()['X-Bar']);
    }

    public function testTextHelper(): void
    {
        $res = Response::text('plain', 200);
        $this->assertSame('plain', $res->getBody());
        $this->assertSame('text/plain; charset=UTF-8', $res->getHeaders()['Content-Type'] ?? null);
    }

    public function testJsonHelper(): void
    {
        $res = Response::json(['a' => 1]);
        $this->assertSame('application/json; charset=UTF-8', $res->getHeaders()['Content-Type'] ?? null);
        $this->assertSame('{"a":1}', $res->getBody());
    }

    public function testHtmlHelper(): void
    {
        $res = Response::html('<b>ok</b>', 200);
        $this->assertSame('<b>ok</b>', $res->getBody());
        $this->assertSame('text/html; charset=UTF-8', $res->getHeaders()['Content-Type'] ?? null);
    }
}
