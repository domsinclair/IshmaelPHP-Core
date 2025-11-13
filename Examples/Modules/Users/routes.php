<?php
declare(strict_types=1);

use Ishmael\Core\Router;
use Modules\Users\Controllers\UsersController;
use Modules\Users\Controllers\AuthController;
use Ishmael\Core\Http\Middleware\VerifyCsrfToken;
use Ishmael\Core\Http\Middleware\ThrottleMiddleware;

/**
 * Register Users module routes.
 * @param Router $router Router instance
 * @return void
 */
return function (Router $router): void {
    // Admin users management (protected)
    $router->group(['prefix' => '/admin/users', 'middleware' => [Modules\Users\Middleware\RequireAdminMiddleware::class]], function () use ($router) {
        $router->get('/', [UsersController::class, 'index'])->name('users.index');
        $router->get('/create', [UsersController::class, 'create'])->name('users.create');
        $router->post('/', [UsersController::class, 'store'], [VerifyCsrfToken::class])->name('users.store');
        $router->get('/{id}/edit', [UsersController::class, 'edit'])->name('users.edit');
        $router->post('/{id}', [UsersController::class, 'update'], [VerifyCsrfToken::class])->name('users.update');
        $router->post('/{id}/delete', [UsersController::class, 'destroy'], [VerifyCsrfToken::class])->name('users.destroy');
        $router->post('/{id}/attach-role', [UsersController::class, 'attachRole'], [VerifyCsrfToken::class])->name('users.attachRole');
        $router->post('/{id}/detach-role', [UsersController::class, 'detachRole'], [VerifyCsrfToken::class])->name('users.detachRole');
    });

    // Auth routes
    $router->group(['prefix' => '/auth', 'middleware' => []], function () use ($router) {
        $router->get('/login', [AuthController::class, 'loginForm'])->name('auth.loginForm');
        $router->post('/login', [AuthController::class, 'login'], [ThrottleMiddleware::with(['capacity' => 10, 'refillTokens' => 10, 'refillInterval' => 60])])->name('auth.login');
        $router->post('/logout', [AuthController::class, 'logout'], [VerifyCsrfToken::class])->name('auth.logout');
    });

    // Optional JSON (read-only) for demo
    $router->group(['prefix' => '/api/v1/users', 'middleware' => []], function () use ($router) {
        $router->get('/', [UsersController::class, 'index'])->name('api.users.list');
    });
};
