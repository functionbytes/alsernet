<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskIntegration\Http\Controllers\Managers\Settings\ProvidersController;

Route::patch('/identity', [ProvidersController::class, 'updateIdentitySettings'])->name('identity.update');

Route::prefix('providers')->name('providers.')->group(function () {
    Route::get('/', [ProvidersController::class, 'index'])->name('index');
    Route::get('/create', [ProvidersController::class, 'create'])->name('create');
    Route::post('/', [ProvidersController::class, 'store'])->name('store');
    Route::get('/{provider}/edit', [ProvidersController::class, 'edit'])->name('edit');
    Route::put('/{provider}', [ProvidersController::class, 'update'])->name('update');
    Route::post('/{provider}/toggle', [ProvidersController::class, 'toggle'])->name('toggle');
    Route::delete('/{provider}', [ProvidersController::class, 'destroy'])->name('destroy');
});
