<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskIntegration\Http\Controllers\Managers\Settings\ProvidersController;

// Gate de un solo punto para todo el grupo: antes solo index() comprobaba
// helpdesk_integration_enabled(), dejando create/store/edit/update/toggle/
// destroy/updateIdentitySettings alcanzables con la integracion apagada.
Route::middleware('integration.enabled:integration')->group(function () {
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
});
