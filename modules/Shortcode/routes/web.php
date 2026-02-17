<?php

use Illuminate\Support\Facades\Route;
use Modules\Shortcode\Http\Controllers\ShortcodeController;

Route::prefix('setting')->middleware(['web', 'auth'])->name('setting.')->group(function () {
    Route::prefix('shortcodes')->name('shortcode.')->group(function () {
        Route::get('/', [ShortcodeController::class, 'index'])->name('index');
        Route::get('/reference', [ShortcodeController::class, 'reference'])->name('reference');
    });
});
