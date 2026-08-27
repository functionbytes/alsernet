<?php

use Illuminate\Support\Facades\Route;
use Modules\GiftMessage\Http\Controllers\Admin\GiftMessageConfigController;
use Modules\GiftMessage\Http\Controllers\Admin\GiftMessageController;
use Modules\GiftMessage\Http\Controllers\Admin\GiftMessageFontController;
use Modules\GiftMessage\Http\Controllers\Admin\GiftMessageGenerationController;
use Modules\GiftMessage\Http\Controllers\Admin\GiftMessageOrderController;

Route::middleware(['web', 'auth'])
    ->prefix('panel/giftmessage')
    ->name('giftmessage.')
    ->group(function () {
        Route::get('/', [GiftMessageController::class, 'index'])->name('index');

        Route::post('/orders/search', [GiftMessageOrderController::class, 'search'])->name('orders.search');

        Route::post('/generate', [GiftMessageGenerationController::class, 'generate'])->name('generate');

        Route::get('/history', [GiftMessageGenerationController::class, 'historyIndex'])->name('history.index');
        Route::get('/history/{generation}/view', [GiftMessageGenerationController::class, 'view'])->name('history.view');
        Route::get('/history/{generation}/download', [GiftMessageGenerationController::class, 'download'])->name('history.download');
        Route::delete('/history/{generation}', [GiftMessageGenerationController::class, 'destroy'])->name('history.destroy');
        Route::post('/history/bulk-action', [GiftMessageGenerationController::class, 'bulkAction'])->name('history.bulk-action');
        Route::post('/history/{generation}/regenerate', [GiftMessageGenerationController::class, 'regenerateOrder'])->name('history.regenerate');
    });

Route::middleware(['web', 'auth'])
    ->prefix('panel/settings/giftmessage')
    ->name('settings.giftmessage.')
    ->group(function () {
        Route::get('/', [GiftMessageConfigController::class, 'index'])->name('index');
        Route::post('/images', [GiftMessageConfigController::class, 'uploadImages'])->name('images.store');
        Route::post('/typography', [GiftMessageConfigController::class, 'saveFonts'])->name('typography.update');
        Route::post('/limits', [GiftMessageConfigController::class, 'saveLimits'])->name('limits.update');
        Route::post('/content', [GiftMessageConfigController::class, 'saveContent'])->name('content.update');
        Route::post('/positions', [GiftMessageConfigController::class, 'savePositions'])->name('positions.save');
        Route::post('/preview-metrics', [GiftMessageConfigController::class, 'previewMetrics'])->name('preview.metrics');

        Route::post('/fonts', [GiftMessageFontController::class, 'store'])->name('fonts.store');
        Route::delete('/fonts/{font}', [GiftMessageFontController::class, 'destroy'])->name('fonts.destroy');
    });
