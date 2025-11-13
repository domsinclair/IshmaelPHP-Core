<?php
declare(strict_types=1);

use Ishmael\Core\Router;
use Modules\Contacts\Controllers\ContactsController;

/**
 * Register Contacts module routes.
 * @param Router $router Router instance
 * @return void
 */
return function (Router $router): void {
    $router->group(['prefix' => '/contacts', 'middleware' => []], function () use ($router) {
        $router->get('/', [ContactsController::class, 'index'])->name('contacts.index');
        $router->get('/create', [ContactsController::class, 'create'])->name('contacts.create');
        $router->post('/', [ContactsController::class, 'store'], ['csrf'])->name('contacts.store');
        $router->get('/{id}', [ContactsController::class, 'show'])->name('contacts.show');
        $router->get('/{id}/edit', [ContactsController::class, 'edit'])->name('contacts.edit');
        $router->post('/{id}', [ContactsController::class, 'update'], ['csrf'])->name('contacts.update');
        $router->post('/{id}/delete', [ContactsController::class, 'destroy'], ['csrf'])->name('contacts.destroy');
    });
};
