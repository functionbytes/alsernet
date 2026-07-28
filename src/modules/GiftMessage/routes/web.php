<?php

use Illuminate\Support\Facades\Route;
use Modules\GiftMessage\Http\Controllers\Admin\GiftMessageConfigController;
use Modules\GiftMessage\Http\Controllers\Admin\GiftMessageController;
use Modules\GiftMessage\Http\Controllers\Admin\GiftMessageGenerationController;
use Modules\GiftMessage\Http\Controllers\Admin\GiftMessageOrderController;

Route::middleware(['web', 'auth'])
    ->prefix('panel/giftmessage')
    ->name('giftmessage.')
    ->group(function () {
        Route::get('/', [GiftMessageController::class, 'index'])->name('index');

        Route::post('/images', [GiftMessageConfigController::class, 'uploadImages'])->name('images.store');
        Route::post('/fonts', [GiftMessageConfigController::class, 'saveFonts'])->name('fonts.update');
        Route::post('/positions', [GiftMessageConfigController::class, 'savePositions'])->name('positions.save');

        Route::post('/orders/search', [GiftMessageOrderController::class, 'search'])->name('orders.search');

        Route::post('/generate', [GiftMessageGenerationController::class, 'generate'])->name('generate');

        Route::get('/history', [GiftMessageGenerationController::class, 'historyIndex'])->name('history.index');
        Route::get('/history/{generation}/download', [GiftMessageGenerationController::class, 'download'])->name('history.download');
        Route::delete('/history/{generation}', [GiftMessageGenerationController::class, 'destroy'])->name('history.destroy');
        Route::post('/history/bulk-action', [GiftMessageGenerationController::class, 'bulkAction'])->name('history.bulk-action');
    });
