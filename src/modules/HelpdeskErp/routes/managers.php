<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskErp\Http\Controllers\Managers\ErpContextWebController;

Route::prefix('erp')
    ->name('manager.helpdesk.erp.')
    ->group(function () {
        Route::get('/context', [ErpContextWebController::class, 'context'])->name('context');
        Route::get('/orders/{customerId}/{orderId}', [ErpContextWebController::class, 'orderDetail'])
            ->whereNumber(['customerId', 'orderId'])
            ->name('orders.detail');
    });
