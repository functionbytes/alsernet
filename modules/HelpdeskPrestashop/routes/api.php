<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskPrestashop\Http\Controllers\Api\CustomerContextController;
use Modules\HelpdeskPrestashop\Http\Controllers\Api\OrderController;

// Customer context
Route::get('/customers/{email}/context', [CustomerContextController::class, 'show'])
    ->name('customers.context')
    ->where('email', '.+');

Route::post('/customers/{email}/context/refresh', [CustomerContextController::class, 'refresh'])
    ->name('customers.context.refresh')
    ->where('email', '.+');

// Orders
Route::get('/orders/{order}/detail', [OrderController::class, 'detail'])
    ->name('orders.detail')
    ->whereNumber('order');

Route::post('/orders/{order}/start-return', [OrderController::class, 'startReturn'])
    ->name('orders.start-return')
    ->whereNumber('order');
