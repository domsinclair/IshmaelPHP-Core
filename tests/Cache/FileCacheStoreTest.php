<?php
declare(strict_types=1);

use Ishmael\Core\Cache\FileCacheStore;
use PHPUnit\Framework\TestCase;

final class FileCacheStoreTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ish_file_cache_tests_' . bin2hex(random_bytes(3));
        @mkdir($this->dir, 0777, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->dir)) {
            foreach (glob($this->dir . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR) ?: [] as $nsDir) {
                foreach (glob($nsDir . DIRECTORY_SEPARATOR . '*.cache.json') ?: [] as $f) {
                    @unlink($f);
                }
                @rmdir($nsDir);
            }
            @rmdir($this->dir);
        }
    }

    public function testWindowsPathQuirksInKeys(): void
    {
        $store = new FileCacheStore($this->dir, 'pref');
        $key1 = 'user:profile:123';
        $key2 = 'path \\server\\share/file/name'; // contains backslashes
        $key3 = 'url /api/v1/items?sort=asc'; // contains slashes and symbols

        $store->set($key1, ['ok' => 1], null, 'ns');
        $store->set($key2, 'B', null, 'ns');
        $store->set($key3, 42, null, 'ns');

        $this->assertTrue($store->has($key1, 'ns'));
        $this->assertTrue($store->has($key2, 'ns'));
        $this->assertTrue($store->has($key3, 'ns'));

        $this->assertEquals(['ok' => 1], $store->get($key1, null, 'ns'));
        $this->assertSame('B', $store->get($key2, null, 'ns'));
        $this->assertSame(42, $store->get($key3, null, 'ns'));
    }

    public function testTtlAndTagEviction(): void
    {
        $store = new FileCacheStore($this->dir);
        $store->set('x', 1, 1, 'alpha', ['hot']);
        $store->set('y', 2, null, 'alpha', ['cold']);
        $this->assertTrue($store->has('x', 'alpha'));
        sleep(2);
        $store->purgeExpired('alpha');
        $this->assertFalse($store->has('x', 'alpha'));
        $this->assertTrue($store->has('y', 'alpha'));
        $store->clearTag('cold', 'alpha');
        $this->assertFalse($store->has('y', 'alpha'));
    }
}
