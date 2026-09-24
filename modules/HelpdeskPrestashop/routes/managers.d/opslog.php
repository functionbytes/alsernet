<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskPrestashop\Http\Controllers\Managers\Ext\OpslogBridgeLogController;
use Modules\HelpdeskPrestashop\Http\Controllers\Managers\Ext\OpslogWebhooksController;

// Pantallas de operación de la integración (piezas 37 y 38): solo para quien
// tenga helpdeskprestashop.ops.view (responsables y administradores). Cada
// operación comprueba además su propio permiso en el controlador.
Route::middleware('can:helpdeskprestashop.ops.view')
    ->prefix('/ps/ext/opslog')
    ->group(function () {
        // 37 · Registro del puente
        Route::get('/bridge-log', [OpslogBridgeLogController::class, 'index'])
            ->name('manager.helpdesk.ps.ext.opslog.bridge-log');

        Route::get('/bridge-log/data', [OpslogBridgeLogController::class, 'data'])
            ->middleware('throttle:30,1')
            ->name('manager.helpdesk.ps.ext.opslog.bridge-log.data');

        Route::post('/bridge-log/requeue', [OpslogBridgeLogController::class, 'requeue'])
            ->middleware('throttle:6,1')
            ->name('manager.helpdesk.ps.ext.opslog.bridge-log.requeue');

        Route::post('/bridge-log/warm-cache', [OpslogBridgeLogController::class, 'warmCache'])
            ->middleware('throttle:6,1')
            ->name('manager.helpdesk.ps.ext.opslog.bridge-log.warm-cache');

        // 38 · Eventos recibidos
        Route::get('/events', [OpslogWebhooksController::class, 'index'])
            ->name('manager.helpdesk.ps.ext.opslog.events');

        Route::post('/events/{event}/reprocess', [OpslogWebhooksController::class, 'reprocess'])
            ->whereNumber('event')
            ->middleware('throttle:30,1')
            ->name('manager.helpdesk.ps.ext.opslog.events.reprocess');
    });
