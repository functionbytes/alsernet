<?php

use Illuminate\Support\Facades\Route;
use Modules\EcommercePayment\Http\Controllers\Admin\BankTransferSettingsController;
use Modules\EcommercePayment\Http\Controllers\Admin\CodSettingsController;
use Modules\EcommercePayment\Http\Controllers\Admin\PaymentMethodsController;
use Modules\EcommercePayment\Http\Controllers\BankTransferController;
use Modules\EcommercePayment\Http\Controllers\PaymentController;
use Modules\EcommercePayment\Http\Controllers\WompiController;

/*
|--------------------------------------------------------------------------
| Web Routes - EcommercePayment Module
|--------------------------------------------------------------------------
*/

// Public routes
Route::post('checkout/payment', [PaymentController::class, 'process'])->name('checkout.payment');

// Bank transfer instructions (public - shown after order is placed)
Route::get('payment/bank-transfer/instructions/{order}', [BankTransferController::class, 'instructions'])
    ->name('payment.bank-transfer.instructions');

// Wompi callbacks (public - called by Wompi servers or redirect)
Route::prefix('payment/wompi')->name('payment.wompi.')->group(function () {
    Route::get('callback', [WompiController::class, 'callback'])->name('callback');
    Route::post('webhook', [WompiController::class, 'webhook'])
        ->name('webhook')
        ->middleware('throttle:wompi-webhook');
    Route::get('transaction/{transactionId}', [WompiController::class, 'checkTransaction'])->name('transaction');
    Route::get('simulate-webhook/{transactionId}', [WompiController::class, 'simulateWebhook'])->name('simulate-webhook');
    Route::get('debug', [WompiController::class, 'debugConfig'])->name('debug');

    // Mobile hosted checkout: shows the Wompi widget on a mobile-friendly page
    // The page injects ?source=mobile&return_url=... into the redirect_url so
    // the callback knows to deep link back into the Flutter app.
    Route::get('mobile-checkout/{token}', [WompiController::class, 'mobileCheckout'])
        ->name('mobile-checkout');
});

// Admin routes
Route::middleware(['auth'])->prefix('panel/ecommerce/payments')->name('ecommerce-payment.')->group(function () {
    Route::get('/', [Modules\EcommercePayment\Http\Controllers\Admin\PaymentController::class, 'index'])->name('payments.index');
    Route::get('/export', [Modules\EcommercePayment\Http\Controllers\Admin\PaymentController::class, 'export'])->name('payments.export');
    Route::get('/methods', [PaymentMethodsController::class, 'index'])->name('methods.index');
    Route::get('/settings', [WompiController::class, 'settings'])->name('settings');
    Route::put('/settings', [WompiController::class, 'updateSettings'])->name('settings.update');
    Route::get('/cod/settings', [CodSettingsController::class, 'index'])->name('cod.settings');
    Route::put('/cod/settings', [CodSettingsController::class, 'update'])->name('cod.settings.update');
    Route::get('/bank-transfer/settings', [BankTransferSettingsController::class, 'index'])->name('bank-transfer.settings');
    Route::put('/bank-transfer/settings', [BankTransferSettingsController::class, 'update'])->name('bank-transfer.settings.update');
    Route::get('/{payment}', [Modules\EcommercePayment\Http\Controllers\Admin\PaymentController::class, 'show'])->name('payments.show');
    Route::post('/{payment}/refund', [Modules\EcommercePayment\Http\Controllers\Admin\PaymentController::class, 'refund'])->name('payments.refund');
    Route::post('/{payment}/confirm', [Modules\EcommercePayment\Http\Controllers\Admin\PaymentController::class, 'confirmPayment'])->name('payments.confirm');
});
