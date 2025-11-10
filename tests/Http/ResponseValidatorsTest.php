<?php
declare(strict_types=1);

use Ishmael\Core\Http\Response;
use PHPUnit\Framework\TestCase;

final class ResponseValidatorsTest extends TestCase
{
    public function testWithEtagQuotesAndWeakPrefixing(): void
    {
        $res = Response::text('ok')->withEtag('abc123');
        $headers = $res->getHeaders();
        $this->assertSame('"abc123"', $headers['ETag'] ?? null, 'Strong ETag should be quoted');

        $res2 = Response::text('ok')->withEtag('abc123', true);
        $this->assertSame('W/"abc123"', $res2->getHeaders()['ETag'] ?? null, 'Weak ETag should be prefixed with W/');

        $res3 = Response::text('ok')->withEtag('W/"x"');
        $this->assertSame('W/"x"', $res3->getHeaders()['ETag'] ?? null, 'Preformatted weak ETag should be preserved');
    }

    public function testWithLastModifiedFormatsRfc7231(): void
    {
        $dt = new DateTimeImmutable('2025-01-02 03:04:05', new DateTimeZone('UTC'));
        $res = Response::text('ok')->withLastModified($dt);
        $lm = $res->getHeaders()['Last-Modified'] ?? '';
        $expected = gmdate('D, d M Y H:i:s \G\M\T', $dt->getTimestamp());
        $this->assertSame($expected, $lm);
    }
}
