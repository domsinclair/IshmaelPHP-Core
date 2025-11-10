<?php
declare(strict_types=1);

namespace Modules\Contacts\Controllers;

use Ishmael\Core\Controller;
use Ishmael\Core\Database;

final class ContactsController extends Controller
{
    public function index(): void
    {
        $adapter = Database::adapter();
        $rows = $adapter->query('SELECT id, first_name AS firstName, last_name AS lastName, email, phone, notes FROM contacts ORDER BY id DESC LIMIT 50')->all();
        $this->render('contacts/index', [
            'items' => $rows,
            'title' => 'Contacts',
        ]);
    }

    public function create(): void
    {
        $this->render('contacts/create', [
            'title' => 'New Contact',
        ]);
    }

    public function store(): void
    {
        $body = $_POST ?? [];
        $data = [
            'firstName' => (string)($body['firstName'] ?? ''),
            'lastName' => (string)($body['lastName'] ?? ''),
            'email' => (string)($body['email'] ?? ''),
            'phone' => (string)($body['phone'] ?? ''),
            'notes' => (string)($body['notes'] ?? ''),
        ];
        $now = date('Y-m-d H:i:s');
        $adapter = Database::adapter();
        $adapter->execute(
            'INSERT INTO contacts (first_name, last_name, email, phone, notes, created_at, updated_at) VALUES (?,?,?,?,?,?,?)',
            [$data['firstName'], $data['lastName'], $data['email'], $data['phone'], $data['notes'], $now, $now]
        );
        header('Location: ' . \Ishmael\Core\Router::url('contacts.index'));
        http_response_code(302);
    }

    public function show(int $id): void
    {
        $adapter = Database::adapter();
        $row = $adapter->query('SELECT id, first_name AS firstName, last_name AS lastName, email, phone, notes FROM contacts WHERE id = ?', [$id])->first();
        if (!$row) {
            http_response_code(404);
            echo 'Not found';
            return;
        }
        $this->render('contacts/show', ['item' => $row, 'title' => 'View Contact']);
    }

    public function edit(int $id): void
    {
        $adapter = Database::adapter();
        $row = $adapter->query('SELECT id, first_name AS firstName, last_name AS lastName, email, phone, notes FROM contacts WHERE id = ?', [$id])->first();
        if (!$row) {
            http_response_code(404);
            echo 'Not found';
            return;
        }
        $this->render('contacts/edit', ['item' => $row, 'title' => 'Edit Contact']);
    }

    public function update(int $id): void
    {
        $body = $_POST ?? [];
        $data = [
            'firstName' => (string)($body['firstName'] ?? ''),
            'lastName' => (string)($body['lastName'] ?? ''),
            'email' => (string)($body['email'] ?? ''),
            'phone' => (string)($body['phone'] ?? ''),
            'notes' => (string)($body['notes'] ?? ''),
        ];
        $now = date('Y-m-d H:i:s');
        $adapter = Database::adapter();
        $adapter->execute(
            'UPDATE contacts SET first_name=?, last_name=?, email=?, phone=?, notes=?, updated_at=? WHERE id=?',
            [$data['firstName'], $data['lastName'], $data['email'], $data['phone'], $data['notes'], $now, $id]
        );
        header('Location: ' . \Ishmael\Core\Router::url('contacts.show', ['id' => $id]));
        http_response_code(302);
    }

    public function destroy(int $id): void
    {
        $adapter = Database::adapter();
        $adapter->execute('DELETE FROM contacts WHERE id = ?', [$id]);
        header('Location: ' . \Ishmael\Core\Router::url('contacts.index'));
        http_response_code(302);
    }
}
