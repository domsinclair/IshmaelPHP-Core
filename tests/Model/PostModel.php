<?php

declare(strict_types=1);

namespace Ishmael\Tests\Model;

use Ishmael\Core\Attributes\Auditable;
use Ishmael\Core\Database\Schema\ColumnDefinition;
use Ishmael\Core\Database\Schema\TableDefinition;
use Ishmael\Core\Model;

#[Auditable(userAttribution: true)]
class PostModel extends Model
{
    protected static string $table = 'posts';

    public static function schema(): ?TableDefinition
    {
        $td = new TableDefinition('posts');
        $td->columns = [
            new ColumnDefinition('id', 'INTEGER', nullable: false, autoIncrement: true),
            new ColumnDefinition('title', 'TEXT', nullable: false),
            new ColumnDefinition('created_at', 'DATETIME', nullable: false),
            new ColumnDefinition('updated_at', 'DATETIME', nullable: false),
            new ColumnDefinition('created_by', 'INTEGER', nullable: true),
            new ColumnDefinition('updated_by', 'INTEGER', nullable: true),
            new ColumnDefinition('deleted_at', 'DATETIME', nullable: true),
        ];
        return $td;
    }
}
