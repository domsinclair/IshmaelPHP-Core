<?php
declare(strict_types=1);

namespace Modules\Contacts\Database\Seeders;

use Ishmael\Core\DatabaseAdapters\DatabaseAdapterInterface;
use Ishmael\Core\Database\Seeders\BaseSeeder;
use Psr\Log\LoggerInterface;

final class ContactsSeeder extends BaseSeeder
{
    public function run(DatabaseAdapterInterface $adapter, LoggerInterface $logger): void
    {
        $now = date('Y-m-d H:i:s');
        $rows = [
            ['Alice','Andrews','alice@example.com','+1 555-0101','Met at conference'],
            ['Bob','Baxter','bob@example.com','+1 555-0102','Friend of Alice'],
            ['Carla','Clark','carla@example.com','+1 555-0103',''],
        ];
        foreach ($rows as [$first, $last, $email, $phone, $notes]) {
            $adapter->execute(
                'INSERT INTO contacts (first_name, last_name, email, phone, notes, created_at, updated_at) VALUES (?,?,?,?,?,?,?)',
                [$first, $last, $email, $phone, $notes, $now, $now]
            );
        }
        $logger->info('ContactsSeeder inserted demo contacts', ['count' => count($rows)]);
    }
}
