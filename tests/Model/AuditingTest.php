<?php

declare(strict_types=1);

namespace Ishmael\Tests\Model;

use Ishmael\Core\Attributes\Auditable;
use Ishmael\Core\Auth\AuthContext;
use Ishmael\Core\DatabaseAdapters\DatabaseAdapterFactory;
use Ishmael\Core\DatabaseAdapters\DatabaseAdapterInterface;
use Ishmael\Core\Database\Schema\ColumnDefinition;
use Ishmael\Core\Database\Schema\TableDefinition;
use Ishmael\Core\Database;
use Ishmael\Core\Model;
use Ishmael\Tests\Model\PostModel;
use PHPUnit\Framework\TestCase;

final class AuditingTest extends TestCase
{
    private DatabaseAdapterInterface $adapter;
    protected function setUp(): void
    {
        DatabaseAdapterFactory::registerDefaults();
        $this->adapter = DatabaseAdapterFactory::create('sqlite');
        $this->adapter->connect(['database' => ':memory:']);
        Database::initAdapter($this->adapter);
    // Create a simple table with auditing columns
        $this->adapter->execute('CREATE TABLE posts (
            id INTEGER PRIMARY KEY,
            title TEXT NOT NULL,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL,
            created_by INTEGER NULL,
            updated_by INTEGER NULL,
            deleted_at TEXT NULL
        )');
    }


    public function testInsertSetsTimestampsAndUserFields(): void
    {
        AuthContext::setCurrentUserId(42);
        $id = PostModel::insert(['title' => 'Hello']);
        $this->assertNotEmpty($id);
        $row = $this->adapter->query('SELECT * FROM posts WHERE id = ?', [$id])->fetch();
        $this->assertIsArray($row);
        $this->assertSame('Hello', $row['title']);
        $this->assertNotEmpty($row['created_at']);
        $this->assertNotEmpty($row['updated_at']);
        $this->assertSame(42, (int)$row['created_by']);
        $this->assertSame(42, (int)$row['updated_by']);
    }

    public function testUpdateBumpsUpdatedAtAndUser(): void
    {
        AuthContext::setCurrentUserId(100);
        $id = PostModel::insert(['title' => 'A']);
        $first = $this->adapter->query('SELECT updated_at, updated_by FROM posts WHERE id = ?', [$id])->fetch();
        $this->assertIsArray($first);
// Change user and update
        AuthContext::setCurrentUserId(101);
        usleep(1000);
// ensure timestamp difference at least 1ms
        PostModel::update($id, ['title' => 'B']);
        $second = $this->adapter->query('SELECT updated_at, updated_by FROM posts WHERE id = ?', [$id])->fetch();
        $this->assertIsArray($second);
        $this->assertNotSame($first['updated_at'], $second['updated_at']);
        $this->assertSame(101, (int)$second['updated_by']);
    }
}
