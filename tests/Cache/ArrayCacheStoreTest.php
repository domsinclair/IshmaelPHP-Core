<?php
declare(strict_types=1);

use Ishmael\Core\Cache\ArrayCacheStore;
use PHPUnit\Framework\TestCase;

final class ArrayCacheStoreTest extends TestCase
{
    public function testSetGetAndSerialization(): void
    {
        $store = new ArrayCacheStore();
        $obj = (object)['a' => 1, 'b' => [1,2,3]];
        $store->set('k1', $obj, null, 'ns1', ['t1','t2']);
        $this->assertTrue($store->has('k1', 'ns1'));
        $loaded = $store->get('k1', null, 'ns1');
        $this->assertIsObject($loaded);
        $this->assertEquals($obj, $loaded);
    }

    public function testTtlExpiryEviction(): void
    {
        $store = new ArrayCacheStore();
        $store->set('exp', 'soon', 1, 'ns2');
        $this->assertTrue($store->has('exp', 'ns2'));
        sleep(2);
        $this->assertFalse($store->has('exp', 'ns2'));
        $this->assertNull($store->get('exp', null, 'ns2'));
    }

    public function testClearByTagAndNamespace(): void
    {
        $store = new ArrayCacheStore();
        $store->set('a', 1, null, 'n', ['users']);
        $store->set('b', 2, null, 'n', ['posts']);
        $store->set('c', 3, null, 'n', ['users']);
        $store->clearTag('users', 'n');
        $this->assertFalse($store->has('a', 'n'));
        $this->assertTrue($store->has('b', 'n'));
        $this->assertFalse($store->has('c', 'n'));
        $store->clearNamespace('n');
        $this->assertFalse($store->has('b', 'n'));
    }
}
