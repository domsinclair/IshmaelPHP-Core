<?php
declare(strict_types=1);

use Ishmael\Core\Database\Migrations\BaseMigration;

/**
 * Example migration demonstrating up()/down() with raw SQL or adapter helpers.
 */
final class CreateExampleTable extends BaseMigration
{
    /** Apply the migration. */
    public function up(): void
    {
        // Raw SQL example (SQLite syntax as a placeholder; edit as needed):
        // $this->sql('CREATE TABLE example (id INTEGER PRIMARY KEY, name TEXT NOT NULL)');
    }

    /** Revert the migration. */
    public function down(): void
    {
        // $this->sql('DROP TABLE IF EXISTS example');
    }
}
