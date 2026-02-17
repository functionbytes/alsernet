<?php

use Illuminate\Support\Facades\Route;
use Modules\Menu\Http\Controllers\MenuController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('menus', MenuController::class)->names('menu');

    // Menu structure update
    Route::post('menus/{menu}/structure', [MenuController::class, 'updateStructure'])
        ->name('menu.structure.update');

    // Menu items
    Route::post('menus/{menu}/items', [MenuController::class, 'storeItem'])
        ->name('menu.items.store');

    Route::put('menus/{menu}/items/{item}', [MenuController::class, 'updateItem'])
        ->name('menu.items.update');

    Route::delete('menus/{menu}/items/{item}', [MenuController::class, 'destroyItem'])
        ->name('menu.items.destroy');
});
