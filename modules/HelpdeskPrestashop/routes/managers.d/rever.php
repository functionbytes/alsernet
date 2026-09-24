<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskPrestashop\Http\Controllers\Managers\Ext\ReverExchangesController;

// Extensión "rever": cambios de producto gestionados por REVER. Solo lecturas
// en la tienda; atadas a {customer} para verificar la propiedad server-side.
Route::get('/customers/{customer}/ps/ext/rever/exchanges', [ReverExchangesController::class, 'customer'])
    ->middleware('throttle:60,1')
    ->name('manager.helpdesk.ps.ext.rever.customer');

Route::get('/customers/{customer}/ps/ext/rever/orders/{order}', [ReverExchangesController::class, 'order'])
    ->whereNumber('order')
    ->middleware('throttle:60,1')
    ->name('manager.helpdesk.ps.ext.rever.order');
