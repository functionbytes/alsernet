<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskPrestashop\Http\Controllers\Managers\Ext\AccountController;

// Cuenta del cliente en PrestaShop (workspace de cliente · sección "Cuenta"):
// ficha editable, grupo y descuento, acceso a la cuenta y RGPD. Las
// escrituras llevan throttle; el permiso y el acceso al cliente los comprueba
// el controlador.
Route::prefix('customers/{customer}/ps/account')
    ->name('manager.helpdesk.ps.ext.account.')
    ->group(function () {
        Route::get('/', [AccountController::class, 'show'])->name('show');

        Route::patch('/', [AccountController::class, 'update'])
            ->middleware('throttle:20,1')
            ->name('update');

        Route::post('/group', [AccountController::class, 'group'])
            ->middleware('throttle:10,1')
            ->name('group');

        Route::post('/password-reset', [AccountController::class, 'passwordReset'])
            ->middleware('throttle:5,1')
            ->name('password-reset');

        Route::get('/gdpr-export', [AccountController::class, 'gdprExport'])
            ->middleware('throttle:10,1')
            ->name('gdpr-export');

        Route::post('/erasure-request', [AccountController::class, 'erasureRequest'])
            ->middleware('throttle:5,1')
            ->name('erasure-request');
    });
