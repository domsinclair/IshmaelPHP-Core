<?php
declare(strict_types=1);

namespace Modules\TodoApi\Controllers;

use Ishmael\Core\Controller;
use Ishmael\Core\Database;

final class TodoController extends Controller
{
    public function list(): void
    {
        $adapter = Database::adapter();
        $items = $adapter->query('SELECT id, title, completed, created_at, updated_at FROM todos ORDER BY id DESC')->all();
        $etag = sha1(json_encode($items, JSON_UNESCAPED_SLASHES));
        header('ETag: W/"' . $etag . '"');
        $this->json(['data' => $items]);
    }

    public function get(int $id): void
    {
        $adapter = Database::adapter();
        $row = $adapter->query('SELECT id, title, completed, created_at, updated_at FROM todos WHERE id = ?', [$id])->first();
        if (!$row) {
            $this->json(['error' => 'Not found'], 404);
            return;
        }
        $this->json($row);
    }

    public function create(): void
    {
        // Accept JSON or form body
        $raw = file_get_contents('php://input') ?: '';
        $data = json_decode($raw, true);
        if (!is_array($data)) { $data = $_POST ?? []; }
        $title = trim((string)($data['title'] ?? ''));
        if ($title === '') {
            $this->json(['error' => 'Title is required'], 422);
            return;
        }
        $now = date('Y-m-d H:i:s');
        $adapter = Database::adapter();
        $adapter->execute('INSERT INTO todos (title, completed, created_at, updated_at) VALUES (?,?,?,?)', [$title, 0, $now, $now]);
        $id = (int)$adapter->lastInsertId();
        $this->json(['id' => $id, 'title' => $title, 'completed' => false, 'created_at' => $now, 'updated_at' => $now], 201);
    }

    public function toggle(int $id): void
    {
        $adapter = Database::adapter();
        $row = $adapter->query('SELECT id, completed FROM todos WHERE id = ?', [$id])->first();
        if (!$row) {
            $this->json(['error' => 'Not found'], 404);
            return;
        }
        $new = ((int)$row['completed'] ?? 0) ? 0 : 1;
        $now = date('Y-m-d H:i:s');
        $adapter->execute('UPDATE todos SET completed=?, updated_at=? WHERE id=?', [$new, $now, $id]);
        $this->json(['id' => $id, 'completed' => (bool)$new, 'updated_at' => $now]);
    }

    public function delete(int $id): void
    {
        $adapter = Database::adapter();
        $count = $adapter->execute('DELETE FROM todos WHERE id = ?', [$id]);
        if ($count < 1) {
            $this->json(['error' => 'Not found'], 404);
            return;
        }
        $this->json(['deleted' => true]);
    }
}
