<?php

use Illuminate\Support\Facades\Route;
use Modules\Shortcode\Http\Controllers\ShortcodeController;

Route::prefix('panel/setting')->middleware(['web', 'auth'])->name('setting.')->group(function () {
    Route::prefix('shortcodes')->name('shortcode.')->group(function () {
        Route::get('/', [ShortcodeController::class, 'index'])->name('index');
        Route::get('/reference', [ShortcodeController::class, 'reference'])->name('reference');
        Route::get('/tester', [ShortcodeController::class, 'tester'])->name('tester');

        // Endpoints internos para el admin UI (sesión web, no Sanctum).
        Route::post('/preview', [ShortcodeController::class, 'compilePreview'])
            ->middleware('throttle:120,1')
            ->name('preview');

        Route::post('/stats/reset', [ShortcodeController::class, 'resetStatsWeb'])
            ->name('stats.reset');
    });
});
