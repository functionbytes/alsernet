<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskPrestashop\Http\Controllers\Managers\Ext\SettingsController;

// «Ajustes del chat» (Ajustes → Helpdesk · PrestaShop). Grupo y gate los
// pone el provider: web + auth + integration.enabled:prestashop, prefijo
// panel/helpdesk. El permiso helpdeskprestashop.settings.manage se comprueba
// en el controlador y en el FormRequest.
Route::prefix('ps/settings')
    ->name('manager.helpdesk.ps.ext.settings.')
    ->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('index');

        // POST y no PUT: PUT real vía form da 405 detrás del nginx de Docker.
        Route::post('/', [SettingsController::class, 'update'])
            ->middleware('throttle:20,1')
            ->name('update');

        Route::post('/reset', [SettingsController::class, 'reset'])
            ->middleware('throttle:20,1')
            ->name('reset');
    });
