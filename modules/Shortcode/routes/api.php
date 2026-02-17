<?php

use Illuminate\Support\Facades\Route;
use Modules\Shortcode\Http\Controllers\ShortcodeController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for the Shortcode module.
|
*/

Route::prefix('shortcodes')->name('shortcode.api.')->group(function () {
    // Compile shortcode
    Route::post('/compile', [ShortcodeController::class, 'compile'])->name('compile');

    // Strip shortcodes
    Route::post('/strip', [ShortcodeController::class, 'strip'])->name('strip');

    // List registered shortcodes
    Route::get('/list', [ShortcodeController::class, 'list'])->name('list');

    // Check if shortcode exists
    Route::post('/check', [ShortcodeController::class, 'check'])->name('check');

    // Clear cache
    Route::post('/clear-cache', [ShortcodeController::class, 'clearCache'])->name('clear-cache');
});
