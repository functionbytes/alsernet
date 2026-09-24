<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskPrestashop\Http\Controllers\Managers\Ext\MetricsChatController;

// «Métricas del chat · PrestaShop»: solo lectura del log de actividad. El
// grupo (panel/helpdesk, web+auth+integration.enabled:prestashop) lo pone el
// ServiceProvider; el permiso helpdeskprestashop.metrics.view se comprueba
// en el controlador.
Route::prefix('ps/metrics')
    ->name('manager.helpdesk.ps.ext.metrics.')
    ->group(function () {
        Route::get('/', [MetricsChatController::class, 'index'])->name('index');

        Route::get('/export', [MetricsChatController::class, 'export'])
            ->middleware('throttle:10,1')
            ->name('export');
    });
