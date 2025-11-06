<?php
declare(strict_types=1);

use Ishmael\Core\Cache\DatabaseCacheStore;
use PHPUnit\Framework\TestCase;

final class DatabaseCacheStoreTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
    }

    public function testSetGetAndExpiry(): void
    {
        $store = new DatabaseCacheStore($this->pdo);
        $store->set('a', ['x' => 1], null, 'ns');
        $this->assertTrue($store->has('a', 'ns'));
        $this->assertEquals(['x' => 1], $store->get('a', null, 'ns'));

        $store->set('b', 'expire', 1, 'ns');
        $this->assertTrue($store->has('b', 'ns'));
        sleep(2);
        $store->purgeExpired('ns');
        $this->assertFalse($store->has('b', 'ns'));
        $this->assertNull($store->get('b', null, 'ns'));
    }

    public function testTagsAndNamespaceClear(): void
    {
        $store = new DatabaseCacheStore($this->pdo);
        $store->set('u1', 1, null, 'users', ['group:1']);
        $store->set('u2', 2, null, 'users', ['group:2']);
        $store->set('u3', 3, null, 'users', ['group:1']);

        $store->clearTag('group:1', 'users');
        $this->assertFalse($store->has('u1', 'users'));
        $this->assertTrue($store->has('u2', 'users'));
        $this->assertFalse($store->has('u3', 'users'));

        $store->clearNamespace('users');
        $this->assertFalse($store->has('u2', 'users'));
    }
}
