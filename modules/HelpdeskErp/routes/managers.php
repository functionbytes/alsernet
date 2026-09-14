<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskErp\Http\Controllers\Managers\ErpContextWebController;

Route::prefix('erp')
    ->name('manager.helpdesk.erp.')
    ->group(function () {
        Route::get('/context', [ErpContextWebController::class, 'context'])->name('context');
        // POST y no PUT: en este stack Docker un PUT real vía AJAX responde 405
        // aunque la ruta exista.
        Route::post('/customers/{customerId}/relink', [ErpContextWebController::class, 'relink'])
            ->whereNumber('customerId')
            ->middleware('throttle:10,1')
            ->name('customers.relink');
        Route::get('/orders/{customerId}/{orderId}', [ErpContextWebController::class, 'orderDetail'])
            ->whereNumber(['customerId', 'orderId'])
            ->name('orders.detail');
    });
