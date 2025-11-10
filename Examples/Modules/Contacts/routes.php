<?php
use Ishmael\Core\Router;
use Modules\Contacts\Controllers\ContactsController;

Router::group(['prefix' => '/contacts', 'middleware' => []], function () {
    Router::get('/', [ContactsController::class, 'index'])->name('contacts.index');
    Router::get('/create', [ContactsController::class, 'create'])->name('contacts.create');
    Router::post('/', [ContactsController::class, 'store'], ['csrf'])->name('contacts.store');
    Router::get('/{id}', [ContactsController::class, 'show'])->name('contacts.show');
    Router::get('/{id}/edit', [ContactsController::class, 'edit'])->name('contacts.edit');
    Router::post('/{id}', [ContactsController::class, 'update'], ['csrf'])->name('contacts.update');
    Router::post('/{id}/delete', [ContactsController::class, 'destroy'], ['csrf'])->name('contacts.destroy');
});
