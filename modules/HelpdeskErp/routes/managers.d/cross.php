<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskErp\Http\Controllers\Managers\ErpCrossController;

/*
 | Extensión "cross": cruce Gestión ↔ tienda y línea de tiempo del cliente.
 | Solo lectura. {customer} es el id del cliente del HELPDESK.
 | El cargador de routes/managers.d/ añade el prefijo 'panel/helpdesk' y el
 | middleware ['web', 'auth'].
 */
Route::prefix('customers/{customer}/erp')
    ->whereNumber('customer')
    ->name('manager.helpdesk.erp.cross.')
    ->middleware('throttle:120,1')
    ->group(function () {
        Route::get('/orders/{orderId}/shop', [ErpCrossController::class, 'shopOrder'])
            ->whereNumber('orderId')
            ->name('shop-order');
        Route::get('/timeline', [ErpCrossController::class, 'timeline'])
            ->name('timeline');
    });
