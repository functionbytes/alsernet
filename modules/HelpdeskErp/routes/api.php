<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskErp\Http\Controllers\Api\ErpContextController;

// Customer search (must be before {email} wildcard routes)
Route::get('/customers/search', [ErpContextController::class, 'search'])
    ->name('customers.search');

// Customer ERP context
Route::get('/customers/{email}/context', [ErpContextController::class, 'show'])
    ->middleware('audit.access:erp,context_view')
    ->name('customers.context')
    ->where('email', '.+');

// Customer timeline (cross-source aggregation)
Route::get('/customers/{email}/timeline', [ErpContextController::class, 'timeline'])
    ->middleware('audit.access:erp,timeline_view')
    ->name('customers.timeline')
    ->where('email', '.+');

// Refresh cached context for a customer
Route::post('/customers/{email}/context/refresh', [ErpContextController::class, 'refresh'])
    ->middleware('audit.access:erp,context_refresh')
    ->name('customers.context.refresh')
    ->where('email', '.+');

// Order detail (proxied from manager)
Route::get('/customers/{customer}/orders/{order}', [ErpContextController::class, 'orderDetail'])
    ->middleware('audit.access:erp,order_detail_view')
    ->name('customers.orders.detail')
    ->whereNumber('customer')
    ->whereNumber('order');

// Health check for the ERP manager connection
Route::get('/health', [ErpContextController::class, 'health'])
    ->name('health');

// Bulk cache warm
Route::post('/cache/warm', [ErpContextController::class, 'warmCache'])
    ->name('cache.warm');
