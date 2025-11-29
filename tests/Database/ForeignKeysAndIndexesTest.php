<?php
declare(strict_types=1);

namespace Ishmael\Tests\Database;

use Ishmael\Core\DatabaseAdapters\SQLiteAdapter;
use Ishmael\Core\Database\Schema\Blueprint;
use PHPUnit\Framework\TestCase;

final class ForeignKeysAndIndexesTest extends TestCase
{
    public function testCreateTablesWithForeignKeyAndIndexesUsingBlueprint(): void
    {
        $adapter = new SQLiteAdapter();
        $pdo = $adapter->connect(['database' => ':memory:']);

        // Parent table: users
        $users = new Blueprint('users');
        $users->id();
        $users->string('name');
        $adapter->createTable($users->toTableDefinition());

        // Child table: posts with FK to users(id) and an index on title
        $posts = new Blueprint('posts');
        $posts->id();
        $posts->string('title');
        $posts->text('body', nullable: true);
        $posts->foreignId('user_id', 'users', nullable: false, type: 'INTEGER', referencesColumn: 'id', onDelete: 'cascade');
        $posts->index('title');
        $adapter->createTable($posts->toTableDefinition());

        // Validate that the foreign key exists using PRAGMA foreign_key_list
        $stmt = $pdo->query("PRAGMA foreign_key_list('posts')");
        $rows = $stmt->fetchAll();
        $this->assertNotEmpty($rows, 'Expected at least one foreign key on posts');
        $this->assertSame('users', $rows[0]['table']);

        // Validate that the index on title exists using PRAGMA index_list
        $stmt = $pdo->query("PRAGMA index_list('posts')");
        $idxRows = $stmt->fetchAll();
        $this->assertNotEmpty($idxRows, 'Expected at least one index on posts');
        $names = array_map(fn($r) => (string)$r['name'], $idxRows);
        $this->assertTrue((bool)array_filter($names, fn($n) => str_starts_with($n, 'idx_posts_title')));
    }
}
