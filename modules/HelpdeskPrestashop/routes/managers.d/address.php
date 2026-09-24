<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskPrestashop\Http\Controllers\Managers\Ext\AddressCountriesController;
use Modules\HelpdeskPrestashop\Http\Controllers\Managers\Ext\AddressShipClaimController;

// Países activos de PrestaShop para el formulario de dirección (pieza 27).
Route::get('/ps/ext/address/countries', [AddressCountriesController::class, 'index'])
    ->name('manager.helpdesk.ps.ext.address.countries');

// Incidencia de envío (pieza 05) → nota interna del pedido en PrestaShop.
Route::post('/customers/{customer}/ps/ext/address/orders/{order}/ship-claim', [AddressShipClaimController::class, 'store'])
    ->whereNumber('order')
    ->middleware('throttle:20,1')
    ->name('manager.helpdesk.ps.ext.address.ship-claim');
