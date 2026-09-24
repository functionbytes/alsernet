<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskPrestashop\Http\Controllers\Managers\Ext\OrdereditReorderController;

// Repetir pedido (pieza 11): atado a {customer} como el resto de acciones de
// pedido; la propiedad del pedido la verifica el bridge contra ese cliente.
Route::get('/customers/{customer}/ps/orders/{order}/reorder', [OrdereditReorderController::class, 'preview'])
    ->whereNumber('order')
    ->middleware('throttle:60,1')
    ->name('manager.helpdesk.ps.ext.orderedit.reorder.preview');

Route::post('/customers/{customer}/ps/orders/{order}/reorder', [OrdereditReorderController::class, 'store'])
    ->whereNumber('order')
    ->middleware('throttle:20,1')
    ->name('manager.helpdesk.ps.ext.orderedit.reorder.store');
