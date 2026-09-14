<?php

use Illuminate\Support\Facades\Route;
use Modules\Storage\Http\Controllers\StorageController;

Route::prefix('storage')
    ->name('storage')
    ->group(function () {
        Route::get('/', [StorageController::class, 'index'])->name('.index');
        Route::delete('/', [StorageController::class, 'destroy'])->name('.destroy');
        Route::get('/create', [StorageController::class, 'create'])->name('.create');
        Route::post('/store', [StorageController::class, 'store'])->name('.store');
        Route::post('/test-connection', [StorageController::class, 'testConnection'])->name('.test-connection');
        Route::get('/{name}/edit', [StorageController::class, 'edit'])->name('.edit')->where('name', '[a-zA-Z0-9_]+');
        Route::patch('/{name}', [StorageController::class, 'updateDisk'])->name('.update-disk')->where('name', '[a-zA-Z0-9_]+');
    });
