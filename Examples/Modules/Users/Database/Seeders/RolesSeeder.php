<?php
declare(strict_types=1);

namespace Modules\Users\Database\Seeders;

use Ishmael\Core\DatabaseAdapters\DatabaseAdapterInterface;
use Ishmael\Core\Database\Seeders\BaseSeeder;
use Psr\Log\LoggerInterface;

final class RolesSeeder extends BaseSeeder
{
    public function run(DatabaseAdapterInterface $adapter, LoggerInterface $logger): void
    {
        $now = date('Y-m-d H:i:s');
        $rows = [
            ['Admin', 'admin'],
            ['Editor', 'editor'],
            ['Viewer', 'viewer'],
        ];
        $inserted = 0;
        foreach ($rows as [$name, $slug]) {
            $exists = $adapter->query('SELECT id FROM roles WHERE slug = ? LIMIT 1', [$slug])->first();
            if ($exists) { continue; }
            $adapter->execute('INSERT INTO roles (name, slug, created_at, updated_at) VALUES (?,?,?,?)', [$name, $slug, $now, $now]);
            $inserted++;
        }
        $logger->info('RolesSeeder ensured roles', ['inserted' => $inserted]);
    }
}
