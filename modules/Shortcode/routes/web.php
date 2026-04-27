<?php

use Illuminate\Support\Facades\Route;
use Modules\Shortcode\Http\Controllers\ShortcodeController;

Route::prefix('panel/settings')->middleware(['web', 'auth'])->name('settings.')->group(function () {
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

// Legacy redirects (singular → plural). TODO remove after 2026-12-31
Route::middleware(['web'])->group(function () {
    Route::redirect('panel/setting/shortcodes/{any}', 'panel/settings/shortcodes/{any}', 301)->where('any', '.*');
    Route::redirect('panel/setting/shortcodes', 'panel/settings/shortcodes', 301);
});
