<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskPrestashop\Http\Controllers\Managers\Ext\CartpayController;

// Cobro de un pedido pendiente (pieza 02): datos de transferencia reales.
Route::get('/customers/{customer}/ps/ext/cartpay/orders/{order}/payment', [CartpayController::class, 'orderPayment'])
    ->whereNumber('order')
    ->middleware('throttle:60,1')
    ->name('manager.helpdesk.ps.ext.cartpay.order-payment');

// Convertir o vaciar el carrito en vivo (pieza 32).
Route::get('/customers/{customer}/ps/ext/cartpay/cart/{cart}/preview', [CartpayController::class, 'preview'])
    ->whereNumber('cart')
    ->middleware('throttle:60,1')
    ->name('manager.helpdesk.ps.ext.cartpay.preview');

Route::post('/customers/{customer}/ps/ext/cartpay/cart/{cart}/convert', [CartpayController::class, 'convert'])
    ->whereNumber('cart')
    ->middleware('throttle:10,1')
    ->name('manager.helpdesk.ps.ext.cartpay.convert');

Route::post('/customers/{customer}/ps/ext/cartpay/cart/{cart}/empty', [CartpayController::class, 'empty'])
    ->whereNumber('cart')
    ->middleware('throttle:10,1')
    ->name('manager.helpdesk.ps.ext.cartpay.empty');
