<?php
declare(strict_types=1);

namespace Modules\Users\Controllers;

use Ishmael\Core\Controller;
use Ishmael\Core\Http\Request;
use Ishmael\Core\Http\Response;
use Modules\Users\Services\UserService;
use Modules\Users\Services\RoleService;

final class UsersController extends Controller
{
    public function __construct(
        private UserService $users = new UserService(),
        private RoleService $roles = new RoleService()
    ) {}

    public function index(Request $req, Response $res): Response
    {
        $q = (string)($req->query('q') ?? '');
        $items = $this->users->list($q);
        ob_start();
        $this->render('users/index', ['items' => $items, 'query' => $q]);
        return $res->setBody((string)ob_get_clean());
    }

    public function create(Request $req, Response $res): Response
    {
        ob_start();
        $this->render('users/create');
        return $res->setBody((string)ob_get_clean());
    }

    public function store(Request $req, Response $res): Response
    {
        $name = (string)($req->input('name') ?? '');
        $email = (string)($req->input('email') ?? '');
        $password = (string)($req->input('password') ?? '');
        $this->users->create($name, $email, $password);
        flash('success', 'User created');
        return $this->redirect(route('users.index'));
    }

    public function edit(Request $req, Response $res, int $id): Response
    {
        $user = $this->users->find($id);
        if (!$user) {
            return $res->setStatusCode(404)->setBody('Not Found');
        }
        $allRoles = $this->roles->all();
        // Fetch user's roles
        $userRoles = \Ishmael\Core\Database::adapter()->query('SELECT r.* FROM roles r INNER JOIN user_roles ur ON ur.role_id=r.id WHERE ur.user_id=?', [$id])->all();
        ob_start();
        $this->render('users/edit', ['user' => $user, 'roles' => $allRoles, 'userRoles' => $userRoles]);
        return $res->setBody((string)ob_get_clean());
    }

    public function update(Request $req, Response $res, int $id): Response
    {
        $name = (string)($req->input('name') ?? '');
        $email = (string)($req->input('email') ?? '');
        $password = (string)($req->input('password') ?? '');
        $this->users->update($id, $name, $email, $password === '' ? null : $password);
        flash('success', 'User updated');
        return $this->redirect(route('users.index'));
    }

    public function destroy(Request $req, Response $res, int $id): Response
    {
        $this->users->delete($id);
        flash('success', 'User deleted');
        return $this->redirect(route('users.index'));
    }

    public function attachRole(Request $req, Response $res, int $id): Response
    {
        $roleId = (int)($req->input('role_id') ?? 0);
        if ($roleId > 0) {
            $this->roles->assign($id, $roleId);
        }
        return $this->redirect(route('users.edit', ['id' => $id]));
    }

    public function detachRole(Request $req, Response $res, int $id): Response
    {
        $roleId = (int)($req->input('role_id') ?? 0);
        if ($roleId > 0) {
            $this->roles->remove($id, $roleId);
        }
        return $this->redirect(route('users.edit', ['id' => $id]));
    }
}
