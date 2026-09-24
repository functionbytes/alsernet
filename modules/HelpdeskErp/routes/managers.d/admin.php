<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskErp\Http\Controllers\Managers\ErpAdminMetricsController;
use Modules\HelpdeskErp\Http\Controllers\Managers\ErpAdminSettingsController;

// «Ajustes de Gestión» y «Métricas de Gestión» (Ajustes → Helpdesk · Gestión
// (ERP)). El grupo (web + auth, prefijo panel/helpdesk) lo pone
// ErpChatExtServiceProvider; los permisos se comprueban en los controladores.
Route::prefix('erp/settings')
    ->name('manager.helpdesk.erp.admin.settings.')
    ->group(function () {
        Route::get('/', [ErpAdminSettingsController::class, 'index'])->name('index');

        // POST y no PUT: un PUT real da 405 detrás del nginx de Docker.
        Route::post('/', [ErpAdminSettingsController::class, 'update'])
            ->middleware('throttle:20,1')
            ->name('update');

        Route::post('/reset', [ErpAdminSettingsController::class, 'reset'])
            ->middleware('throttle:20,1')
            ->name('reset');
    });

Route::prefix('erp/metrics')
    ->name('manager.helpdesk.erp.admin.metrics.')
    ->group(function () {
        Route::get('/', [ErpAdminMetricsController::class, 'index'])->name('index');

        Route::get('/export', [ErpAdminMetricsController::class, 'export'])
            ->middleware('throttle:10,1')
            ->name('export');
    });
