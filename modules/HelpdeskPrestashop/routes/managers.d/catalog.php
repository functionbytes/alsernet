<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskPrestashop\Http\Controllers\Managers\Ext\CatalogController;

// Extensión "catalog" (piezas 10, 16, 25 y 26). Mismo grupo que managers.php:
// panel/helpdesk + web + auth + integration.enabled:prestashop.

// Stock por ubicación, plazo y precio del grupo del cliente de un producto
Route::get('/customers/{customer}/ps/ext/catalog/products/{product}/sheet', [CatalogController::class, 'sheet'])
    ->whereNumber('product')
    ->middleware('throttle:120,1')
    ->name('manager.helpdesk.ps.ext.catalog.sheet');

// Comparar 2-3 productos
Route::post('/customers/{customer}/ps/ext/catalog/compare', [CatalogController::class, 'compare'])
    ->middleware('throttle:60,1')
    ->name('manager.helpdesk.ps.ext.catalog.compare');

// Aviso de vuelta a stock (escritura en la tienda)
Route::post('/customers/{customer}/ps/ext/catalog/products/{product}/stock-alert', [CatalogController::class, 'stockAlert'])
    ->whereNumber('product')
    ->middleware('throttle:20,1')
    ->name('manager.helpdesk.ps.ext.catalog.stock_alert');
