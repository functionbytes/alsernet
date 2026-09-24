<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskErp\Http\Controllers\Managers\ErpChatController;

/*
 | Extensión "polish" de Gestión en el chat — SOLO LECTURA.
 | La incluye el cargador de routes/managers.d con prefijo 'panel/helpdesk' y
 | middleware ['web', 'auth']. {customer} es el id del cliente del helpdesk.
 |
 |  GET customers/{customer}/erp/overview/orders
 |      Resumen ligero al terminar el escaneo de pedidos: solo la sección
 |      orders se vuelve a pedir al manager; el resto sale de la caché.
 */
Route::prefix('customers/{customer}/erp')
    ->whereNumber('customer')
    ->name('manager.helpdesk.erp.polish.')
    ->middleware('throttle:120,1')
    ->group(function () {
        Route::get('/overview/orders', [ErpChatController::class, 'overviewOrders'])
            ->name('overview-orders');
    });
