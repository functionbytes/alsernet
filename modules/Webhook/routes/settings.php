<?php

use Illuminate\Support\Facades\Route;
use Modules\Webhook\Http\Controllers\Settings\IntegrationsController;
use Modules\Webhook\Http\Controllers\Settings\SubscriptionsController;

/*
|--------------------------------------------------------------------------
| Webhook Configuration Routes
|--------------------------------------------------------------------------
|
| Rutas para la configuración del sistema de webhooks
| Prefix: /settings/webhooks (aplicado por ServiceProvider)
| Name: webhooks.backups.* (aplicado por ServiceProvider)
| Middleware: web, auth, role:super-admin
|
*/

Route::middleware('can:view-webhook-backups')->group(function () {

    // Integrations management
    Route::prefix('integrations')->name('integrations.')->group(function () {
        Route::get('/', [IntegrationsController::class, 'index'])->name('index');
        Route::get('/create', [IntegrationsController::class, 'create'])->name('create');
        Route::post('/', [IntegrationsController::class, 'store'])->name('store');
        Route::get('/{uid}', [IntegrationsController::class, 'show'])->name('show');
        Route::get('/{uid}/edit', [IntegrationsController::class, 'edit'])->name('edit');
        Route::put('/{uid}', [IntegrationsController::class, 'update'])->name('update');
        Route::delete('/{uid}', [IntegrationsController::class, 'destroy'])->name('destroy');

        // Status management
        Route::post('/{uid}/activate', [IntegrationsController::class, 'activate'])->name('activate');
        Route::post('/{uid}/suspend', [IntegrationsController::class, 'suspend'])->name('suspend');
        Route::post('/{uid}/disable', [IntegrationsController::class, 'disable'])->name('disable');
    });

    // Subscriptions management
    Route::prefix('subscriptions')->name('subscriptions.')->group(function () {
        Route::get('/', [SubscriptionsController::class, 'index'])->name('index');
        Route::get('/create', [SubscriptionsController::class, 'create'])->name('create');
        Route::post('/', [SubscriptionsController::class, 'store'])->name('store');
        Route::get('/{uid}', [SubscriptionsController::class, 'show'])->name('show');
        Route::get('/{uid}/edit', [SubscriptionsController::class, 'edit'])->name('edit');
        Route::put('/{uid}', [SubscriptionsController::class, 'update'])->name('update');
        Route::delete('/{uid}', [SubscriptionsController::class, 'destroy'])->name('destroy');

        // Status management
        Route::post('/{uid}/activate', [SubscriptionsController::class, 'activate'])->name('activate');
        Route::post('/{uid}/deactivate', [SubscriptionsController::class, 'deactivate'])->name('deactivate');

        // Testing
        Route::post('/{uid}/test', [SubscriptionsController::class, 'test'])->name('test');
    });

});
