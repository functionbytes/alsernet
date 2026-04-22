<?php

use Illuminate\Support\Facades\Route;
use Modules\Helpdesk\Http\Controllers\Api\CannedRepliesController;
use Modules\Helpdesk\Http\Controllers\Api\CustomersController;

Route::middleware(['api', 'auth:sanctum', 'throttle:60,1'])
    ->prefix('helpdesk')
    ->name('api.helpdesk.')
    ->group(function () {
        Route::get('customers', [CustomersController::class, 'index'])->name('customers.index');
        Route::post('customers', [CustomersController::class, 'store'])->name('customers.store');
        Route::get('customers/{id}', [CustomersController::class, 'show'])->name('customers.show');

        Route::get('canned-replies', [CannedRepliesController::class, 'index'])->name('canned-replies.index');
    });
