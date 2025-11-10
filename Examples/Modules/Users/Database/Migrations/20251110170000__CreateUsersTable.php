<?php
declare(strict_types=1);

use Ishmael\Core\Database\Migrations\BaseMigration;
use Ishmael\Core\Database\Schema\TableDefinition;
use Ishmael\Core\Database\Schema\ColumnDefinition;
use Ishmael\Core\Database\Schema\IndexDefinition;

final class CreateUsersTable extends BaseMigration
{
    public function up(): void
    {
        $def = new TableDefinition('users');
        $def->addColumn(new ColumnDefinition('id', 'integer', nullable: false, autoIncrement: true));
        $def->addColumn(new ColumnDefinition('name', 'string', length: 190));
        $def->addColumn(new ColumnDefinition('email', 'string', length: 190));
        $def->addColumn(new ColumnDefinition('password_hash', 'string', length: 255));
        $def->addColumn(new ColumnDefinition('remember_token', 'string', nullable: true, length: 100));
        $def->addColumn(new ColumnDefinition('created_at', 'datetime'));
        $def->addColumn(new ColumnDefinition('updated_at', 'datetime'));
        $def->addIndex(new IndexDefinition('users_email_unique', ['email'], 'unique'));
        $this->adapter()->createTable($def);
    }

    public function down(): void
    {
        $this->adapter()->dropTable('users');
    }
}
