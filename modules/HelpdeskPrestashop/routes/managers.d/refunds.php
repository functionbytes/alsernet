<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskPrestashop\Http\Controllers\Managers\Ext\RefundsOrderController;
use Modules\HelpdeskPrestashop\Http\Controllers\Managers\Ext\RefundsRmaController;

// Extensión "refunds" (piezas 08 y 35). Atadas a {customer}: la propiedad del
// pedido/RMA la verifica el puente con el cliente resuelto server-side.
Route::get('/customers/{customer}/ps/ext/refunds/orders/{order}', [RefundsOrderController::class, 'show'])
    ->whereNumber('order')
    ->name('manager.helpdesk.ps.ext.refunds.orders.show');

Route::post('/customers/{customer}/ps/ext/refunds/orders/{order}', [RefundsOrderController::class, 'store'])
    ->whereNumber('order')
    ->middleware('throttle:10,1')
    ->name('manager.helpdesk.ps.ext.refunds.orders.store');

Route::get('/customers/{customer}/ps/ext/refunds/rma/{rma}', [RefundsRmaController::class, 'show'])
    ->whereNumber('rma')
    ->name('manager.helpdesk.ps.ext.refunds.rma.show');

Route::post('/customers/{customer}/ps/ext/refunds/rma/{rma}/state', [RefundsRmaController::class, 'updateState'])
    ->whereNumber('rma')
    ->middleware('throttle:30,1')
    ->name('manager.helpdesk.ps.ext.refunds.rma.state');
