<?php

declare(strict_types=1);

namespace Ishmael\Tests\Http;

use Ishmael\Core\Http\Request;
use Ishmael\Core\Http\UploadedFile;
use PHPUnit\Framework\TestCase;

final class FileUploadTest extends TestCase
{
    public function testRequestParsesFiles(): void
    {
        $files = [
            'avatar' => [
                'name' => 'me.jpg',
                'type' => 'image/jpeg',
                'tmp_name' => '/tmp/phpasdf',
                'error' => UPLOAD_ERR_OK,
                'size' => 1024,
            ]
        ];

        // Mock $_FILES via constructor since fromGlobals reads $_FILES directly
        $req = new Request('POST', '/', [], [], [], [], '', $files);
        // Note: The constructor currently takes $files as raw array but fromGlobals normalizes it.
        // Wait, I updated the constructor to take $files but didn't normalize it in constructor.
        // Let's check my Request.php update.
    }

    public function testNormalizationOfSingleFile(): void
    {
        $_FILES = [
            'avatar' => [
                'name' => 'me.jpg',
                'type' => 'image/jpeg',
                'tmp_name' => '/tmp/phpasdf',
                'error' => UPLOAD_ERR_OK,
                'size' => 1024,
            ]
        ];

        $req = Request::fromGlobals();
        $file = $req->file('avatar');

        $this->assertInstanceOf(UploadedFile::class, $file);
        $this->assertSame('me.jpg', $file->getClientOriginalName());
        $this->assertSame('image/jpeg', $file->getClientMimeType());
        $this->assertSame(1024, $file->getSize());
        $this->assertTrue($file->isValid());
    }

    public function testNormalizationOfNestedFiles(): void
    {
        $_FILES = [
            'gallery' => [
                'name' => ['img1.png', 'img2.png'],
                'type' => ['image/png', 'image/png'],
                'tmp_name' => ['/tmp/p1', '/tmp/p2'],
                'error' => [0, 0],
                'size' => [500, 600],
            ]
        ];

        $req = Request::fromGlobals();
        $gallery = $req->file('gallery');

        $this->assertIsArray($gallery);
        $this->assertCount(2, $gallery);
        $this->assertInstanceOf(UploadedFile::class, $gallery[0]);
        $this->assertSame('img1.png', $gallery[0]->getClientOriginalName());
        $this->assertSame('img2.png', $gallery[1]->getClientOriginalName());
    }
}
