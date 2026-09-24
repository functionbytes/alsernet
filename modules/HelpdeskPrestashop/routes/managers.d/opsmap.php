<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskPrestashop\Http\Controllers\Managers\Ext\OpsmapController;

// Pantallas de administración de la integración (piezas 39 y 40). El permiso
// se comprueba en el controlador/FormRequest: statemap.manage para el mapeo,
// ops.view para la auditoría.
Route::prefix('ps/ops')
    ->name('manager.helpdesk.ps.ext.opsmap.')
    ->group(function () {
        Route::get('/state-map', [OpsmapController::class, 'stateMap'])->name('state-map');

        // POST y no PUT: PUT real vía AJAX/form da 405 detrás del nginx de Docker.
        Route::post('/state-map', [OpsmapController::class, 'saveStateMap'])
            ->middleware('throttle:20,1')
            ->name('state-map.update');

        Route::get('/state-notices', [OpsmapController::class, 'stateNotices'])->name('state-notices');

        Route::post('/state-notices', [OpsmapController::class, 'saveStateNotices'])
            ->middleware('throttle:20,1')
            ->name('state-notices.update');

        Route::get('/audit', [OpsmapController::class, 'audit'])->name('audit');

        Route::get('/audit/export', [OpsmapController::class, 'exportAudit'])
            ->middleware('throttle:10,1')
            ->name('audit.export');
    });
