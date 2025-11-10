<?php
declare(strict_types=1);

namespace Modules\TodoApi\Database\Seeders;

use Ishmael\Core\DatabaseAdapters\DatabaseAdapterInterface;
use Ishmael\Core\Database\Seeders\BaseSeeder;
use Psr\Log\LoggerInterface;

final class TodoSeeder extends BaseSeeder
{
    public function run(DatabaseAdapterInterface $adapter, LoggerInterface $logger): void
    {
        $now = date('Y-m-d H:i:s');
        $rows = [
            ['Write docs', 0],
            ['Ship examples', 1],
        ];
        foreach ($rows as [$title, $completed]) {
            $adapter->execute(
                'INSERT INTO todos (title, completed, created_at, updated_at) VALUES (?,?,?,?)',
                [$title, $completed, $now, $now]
            );
        }
        $logger->info('TodoSeeder inserted demo todos', ['count' => count($rows)]);
    }
}
