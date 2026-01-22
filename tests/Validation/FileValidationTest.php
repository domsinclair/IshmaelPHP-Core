<?php

declare(strict_types=1);

namespace Ishmael\Tests\Validation;

use Ishmael\Core\Http\UploadedFile;
use Ishmael\Core\Validation\Validator;
use PHPUnit\Framework\TestCase;

final class FileValidationTest extends TestCase
{
    private function createMockFile(string $name, string $mime, int $size, int $error = UPLOAD_ERR_OK): UploadedFile
    {
        return new UploadedFile($name, $mime, '/tmp/test', $error, $size);
    }

    public function testFileRule(): void
    {
        $v = new Validator();
        $file = $this->createMockFile('test.txt', 'text/plain', 100);

        $rules = ['document' => 'file'];
        $data = ['document' => $file];

        $clean = $v->validate($data, $rules);
        $this->assertSame($file, $clean['document']);

        $invalidFile = $this->createMockFile('test.txt', 'text/plain', 100, UPLOAD_ERR_INI_SIZE);
        $this->expectException(\Ishmael\Core\Validation\ValidationException::class);
        $v->validate(['document' => $invalidFile], $rules);
    }

    public function testImageRule(): void
    {
        $v = new Validator();
        $rules = ['photo' => 'image'];

        $img = $this->createMockFile('me.jpg', 'image/jpeg', 100);
        $clean = $v->validate(['photo' => $img], $rules);
        $this->assertSame($img, $clean['photo']);

        $v2 = new Validator();
        $notImg = $this->createMockFile('me.txt', 'text/plain', 100);
        $this->expectException(\Ishmael\Core\Validation\ValidationException::class);
        $v2->validate(['photo' => $notImg], $rules);
    }

    public function testMimesRule(): void
    {
        $v = new Validator();
        $rules = ['doc' => 'mimes:pdf,docx'];

        $pdf = $this->createMockFile('test.pdf', 'application/pdf', 100);
        $clean = $v->validate(['doc' => $pdf], $rules);
        $this->assertSame($pdf, $clean['doc']);

        $v2 = new Validator();
        $txt = $this->createMockFile('test.txt', 'text/plain', 100);
        $this->expectException(\Ishmael\Core\Validation\ValidationException::class);
        $v2->validate(['doc' => $txt], $rules);
    }

    public function testMaxFileRule(): void
    {
        $v = new Validator();
        $rules = ['doc' => 'max:10']; // 10 KB

        $small = $this->createMockFile('small.txt', 'text/plain', 5 * 1024);
        $clean = $v->validate(['doc' => $small], $rules);
        $this->assertSame($small, $clean['doc']);

        $v2 = new Validator();
        $large = $this->createMockFile('large.txt', 'text/plain', 15 * 1024);
        $this->expectException(\Ishmael\Core\Validation\ValidationException::class);
        $v2->validate(['doc' => $large], $rules);
    }
}
