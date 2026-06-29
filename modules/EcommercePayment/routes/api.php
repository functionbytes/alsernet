<?php

use Illuminate\Support\Facades\Route;
use Modules\EcommercePayment\Http\Controllers\Api\PaymentStatusController;

/*
|--------------------------------------------------------------------------
| API Routes - EcommercePayment Module
|--------------------------------------------------------------------------
*/

Route::get('payment/status', [PaymentStatusController::class, 'show'])->name('api.payment.status');
