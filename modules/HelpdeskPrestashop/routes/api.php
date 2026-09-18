<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskPrestashop\Http\Controllers\Api\CustomerContextController;
use Modules\HelpdeskPrestashop\Http\Controllers\Api\OrderController;

// Email is validated in the controller via filter_var() — the route accepts
// any non-slash segment so malformed emails still hit the controller and
// return a structured 422 instead of a generic 404.
Route::get('/customers/{email}/context', [CustomerContextController::class, 'show'])
    ->name('customers.context')
    ->where('email', '.+');

Route::post('/customers/{email}/context/refresh', [CustomerContextController::class, 'refresh'])
    ->name('customers.context.refresh')
    ->where('email', '.+');

Route::get('/orders/{order}/detail', [OrderController::class, 'detail'])
    ->name('orders.detail')
    ->whereNumber('order');

Route::post('/orders/{order}/start-return', [OrderController::class, 'startReturn'])
    ->name('orders.start-return')
    ->whereNumber('order');
