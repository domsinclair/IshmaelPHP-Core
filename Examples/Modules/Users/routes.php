<?php
use Ishmael\Core\Router;
use Modules\Users\Controllers\UsersController;
use Modules\Users\Controllers\AuthController;
use Ishmael\Core\Http\Middleware\VerifyCsrfToken;
use Ishmael\Core\Http\Middleware\ThrottleMiddleware;

// Admin users management (protected)
Router::group(['prefix' => '/admin/users', 'middleware' => [Modules\Users\Middleware\RequireAdminMiddleware::class]], function () {
    Router::get('/', [UsersController::class, 'index'])->name('users.index');
    Router::get('/create', [UsersController::class, 'create'])->name('users.create');
    Router::post('/', [UsersController::class, 'store'], [VerifyCsrfToken::class])->name('users.store');
    Router::get('/{id}/edit', [UsersController::class, 'edit'])->name('users.edit');
    Router::post('/{id}', [UsersController::class, 'update'], [VerifyCsrfToken::class])->name('users.update');
    Router::post('/{id}/delete', [UsersController::class, 'destroy'], [VerifyCsrfToken::class])->name('users.destroy');
    Router::post('/{id}/attach-role', [UsersController::class, 'attachRole'], [VerifyCsrfToken::class])->name('users.attachRole');
    Router::post('/{id}/detach-role', [UsersController::class, 'detachRole'], [VerifyCsrfToken::class])->name('users.detachRole');
});

// Auth routes
Router::group(['prefix' => '/auth', 'middleware' => []], function () {
    Router::get('/login', [AuthController::class, 'loginForm'])->name('auth.loginForm');
    Router::post('/login', [AuthController::class, 'login'], [ThrottleMiddleware::with(['capacity' => 10, 'refillTokens' => 10, 'refillInterval' => 60])])->name('auth.login');
    Router::post('/logout', [AuthController::class, 'logout'], [VerifyCsrfToken::class])->name('auth.logout');
});

// Optional JSON (read-only) for demo
Router::group(['prefix' => '/api/v1/users', 'middleware' => []], function () {
    Router::get('/', [UsersController::class, 'index'])->name('api.users.list');
});
