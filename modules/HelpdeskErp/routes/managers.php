<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskErp\Http\Controllers\Managers\ErpChatController;
use Modules\HelpdeskErp\Http\Controllers\Managers\ErpContextWebController;
use Modules\HelpdeskErp\Services\ErpChat\ErpChatSections;

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

/*
 | Gestión (ERP) dentro del chat — solo lectura. {customer} es SIEMPRE el id
 | del cliente del helpdesk; el id ERP se resuelve en servidor.
 */
Route::prefix('customers/{customer}/erp')
    ->whereNumber('customer')
    ->name('manager.helpdesk.erp.chat.')
    ->middleware('throttle:120,1')
    ->group(function () {
        Route::get('/overview', [ErpChatController::class, 'overview'])->name('overview');
        Route::get('/sections/{section}', [ErpChatController::class, 'section'])
            ->where('section', implode('|', array_map('preg_quote', ErpChatSections::names())))
            ->name('section');
        Route::get('/orders/{orderId}', [ErpChatController::class, 'order'])
            ->whereNumber('orderId')
            ->name('order');
        Route::get('/delivery-notes/{deliveryId}', [ErpChatController::class, 'deliveryNote'])
            ->whereNumber('deliveryId')
            ->name('delivery-note');
        Route::get('/invoices/{invoiceId}', [ErpChatController::class, 'invoice'])
            ->whereNumber('invoiceId')
            ->name('invoice');
    });
