<?php
declare(strict_types=1);

use Ishmael\Core\Router;
use Modules\TodoApi\Controllers\TodoController;

/**
 * Register Todo API routes.
 * @param Router $router Router instance
 * @return void
 */
return function (Router $router): void {
    $router->group([
        'prefix' => '/api/v1/todos',
        'middleware' => ['throttle:60,1', 'conditional']
    ], function () use ($router) {
        $router->get('/', [TodoController::class, 'list'])->name('api.todos.list');
        $router->get('/{id}', [TodoController::class, 'get'])->name('api.todos.get');
        $router->post('/', [TodoController::class, 'create'])->name('api.todos.create');
        $router->post('/{id}/toggle', [TodoController::class, 'toggle'])->name('api.todos.toggle');
        $router->post('/{id}/delete', [TodoController::class, 'delete'])->name('api.todos.delete');
    });
};
