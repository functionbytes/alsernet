<?php

use Illuminate\Support\Facades\Route;
use Modules\Storage\Http\Controllers\StorageController;

Route::prefix('storage')
    ->name('storage')
    ->group(function () {
        Route::get('/', [StorageController::class, 'index'])->name('');
        Route::post('/', [StorageController::class, 'update'])->name('.update');
        Route::delete('/', [StorageController::class, 'destroy'])->name('.destroy');
        Route::get('/create', [StorageController::class, 'create'])->name('.create');
        Route::post('/store', [StorageController::class, 'store'])->name('.store');
        Route::get('/{index}/edit', [StorageController::class, 'edit'])->name('.edit');
        Route::patch('/{index}', [StorageController::class, 'updateDisk'])->name('.update-disk');
    });
