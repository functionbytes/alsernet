<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskPrestashop\Http\Controllers\Managers\Ext\PromosShopController;
use Modules\HelpdeskPrestashop\Http\Controllers\Managers\Ext\PromosVoucherController;

// Extensión "promos" (piezas 33 y 34). Mismo grupo que managers.php:
// panel/helpdesk + web + auth + integration.enabled:prestashop.

// Editar / duplicar un cupón propio del cliente
Route::get('/customers/{customer}/ps/ext/promos/vouchers/{voucher}', [PromosVoucherController::class, 'show'])
    ->whereNumber('voucher')
    ->name('manager.helpdesk.ps.ext.promos.voucher.show');

Route::post('/customers/{customer}/ps/ext/promos/vouchers/{voucher}', [PromosVoucherController::class, 'update'])
    ->whereNumber('voucher')
    ->middleware('throttle:10,1')
    ->name('manager.helpdesk.ps.ext.promos.voucher.update');

// Promociones públicas y vigentes de la tienda
Route::get('/ps/ext/promos/shop', [PromosShopController::class, 'index'])
    ->middleware('throttle:60,1')
    ->name('manager.helpdesk.ps.ext.promos.shop');
