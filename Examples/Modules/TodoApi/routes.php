<?php
use Ishmael\Core\Router;
use Modules\TodoApi\Controllers\TodoController;

Router::group([
    'prefix' => '/api/v1/todos',
    'middleware' => ['throttle:60,1','conditional']
], function () {
    Router::get('/', [TodoController::class, 'list'])->name('api.todos.list');
    Router::get('/{id}', [TodoController::class, 'get'])->name('api.todos.get');
    Router::post('/', [TodoController::class, 'create'])->name('api.todos.create');
    Router::post('/{id}/toggle', [TodoController::class, 'toggle'])->name('api.todos.toggle');
    Router::post('/{id}/delete', [TodoController::class, 'delete'])->name('api.todos.delete');
});
