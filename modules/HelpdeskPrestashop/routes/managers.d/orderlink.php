<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskPrestashop\Http\Controllers\Managers\Ext\OrderlinkController;

// Extensión "orderlink": pedidos de PrestaShop ligados a una conversación.
// Solo datos del helpdesk (no hay escritura en la tienda). Permisos en el
// controlador: orders.view + ConversationPolicy (view para ver/ligar, update
// para desligar). POST y no DELETE: PUT/DELETE reales vía AJAX dan 405 detrás
// del nginx de Docker.
Route::prefix('ps/ext/orderlink/conversations/{conversation}')
    ->name('manager.helpdesk.ps.ext.orderlink.')
    ->group(function () {
        Route::get('/', [OrderlinkController::class, 'index'])
            ->whereNumber('conversation')
            ->middleware('throttle:120,1')
            ->name('index');

        Route::post('/', [OrderlinkController::class, 'store'])
            ->whereNumber('conversation')
            ->middleware('throttle:120,1')
            ->name('store');

        Route::post('/{link}/unlink', [OrderlinkController::class, 'destroy'])
            ->whereNumber(['conversation', 'link'])
            ->middleware('throttle:30,1')
            ->name('destroy');
    });
