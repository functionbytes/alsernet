<?php

use Illuminate\Support\Facades\Route;
use Modules\EcommercePayment\Http\Controllers\PaymentController;
use Modules\EcommercePayment\Http\Controllers\WompiController;

/*
|--------------------------------------------------------------------------
| Web Routes - EcommercePayment Module
|--------------------------------------------------------------------------
*/

// Public routes
Route::post('checkout/payment', [PaymentController::class, 'process'])->name('checkout.payment');

// Wompi callbacks (public - called by Wompi servers or redirect)
Route::prefix('payment/wompi')->name('payment.wompi.')->group(function () {
    Route::get('callback', [WompiController::class, 'callback'])->name('callback');
    Route::post('webhook', [WompiController::class, 'webhook'])
        ->name('webhook')
        ->middleware('throttle:wompi-webhook');
    Route::get('transaction/{transactionId}', [WompiController::class, 'checkTransaction'])->name('transaction');
    Route::get('simulate-webhook/{transactionId}', [WompiController::class, 'simulateWebhook'])->name('simulate-webhook');
    Route::get('debug', [WompiController::class, 'debugConfig'])->name('debug');
});

// Admin routes
Route::middleware(['auth'])->prefix('panel/ecommerce/payments')->name('ecommerce-payment.')->group(function () {
    Route::get('/', [Modules\EcommercePayment\Http\Controllers\Admin\PaymentController::class, 'index'])->name('payments.index');
    Route::get('/export', [Modules\EcommercePayment\Http\Controllers\Admin\PaymentController::class, 'export'])->name('payments.export');
    Route::get('/{payment}', [Modules\EcommercePayment\Http\Controllers\Admin\PaymentController::class, 'show'])->name('payments.show');
    Route::post('/{payment}/refund', [Modules\EcommercePayment\Http\Controllers\Admin\PaymentController::class, 'refund'])->name('payments.refund');
    Route::get('settings', [WompiController::class, 'settings'])->name('settings');
    Route::put('settings', [WompiController::class, 'updateSettings'])->name('settings.update');
});
