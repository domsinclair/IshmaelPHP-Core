<?php
declare(strict_types=1);

namespace Modules\Users\Controllers;

use Ishmael\Core\Controller;
use Ishmael\Core\Http\Request;
use Ishmael\Core\Http\Response;
use Modules\Users\Services\UserService;

final class AuthController extends Controller
{
    public function __construct(private UserService $users = new UserService()) {}

    public function loginForm(Request $req, Response $res): Response
    {
        // Render simple login form
        ob_start();
        $this->render('auth/login');
        return $res->setBody((string)ob_get_clean());
    }

    public function login(Request $req, Response $res): Response
    {
        $email = (string)($req->input('email') ?? '');
        $password = (string)($req->input('password') ?? '');
        $user = $this->users->authenticate($email, $password);
        if (!$user) {
            flash('error', 'Invalid credentials');
            return $this->redirect(route('auth.loginForm'));
        }
        // Minimal session set
        /** @var \Ishmael\Core\Session\SessionManager|null $mgr */
        $mgr = session();
        if ($mgr) {
            $mgr->put('user_id', (int)$user['id']);
        }
        flash('success', 'Welcome back');
        return $this->redirect('/');
    }

    public function logout(Request $req, Response $res): Response
    {
        /** @var \Ishmael\Core\Session\SessionManager|null $mgr */
        $mgr = session();
        if ($mgr) {
            $mgr->remove('user_id');
        }
        flash('success', 'Signed out');
        return $this->redirect('/');
    }
}
