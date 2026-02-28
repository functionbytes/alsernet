<?php

use Illuminate\Support\Facades\Route;
use Modules\Page\Http\Controllers\Api\PageAutoSaveController;
use Modules\Page\Http\Controllers\Api\PageCacheController;
use Modules\Page\Http\Controllers\Api\PageLockController;
use Modules\Page\Http\Controllers\PageController;
use Modules\Page\Http\Controllers\PublicController;
use Modules\Page\Http\Controllers\SearchController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    Route::get('pages', [PublicController::class, 'index'])->name('api.pages.index');
    Route::get('pages/search', [SearchController::class, 'search'])->name('api.pages.search');
    Route::get('pages/search/quick', [SearchController::class, 'quickSearch'])->name('api.pages.search.quick');
    Route::get('pages/{slug}', [PublicController::class, 'show'])->name('api.pages.show');

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::apiResource('pages', PageController::class)->names('api.settings.pages');
        Route::post('pages/{page}/publish', [PageController::class, 'publish'])->name('api.settings.pages.publish');
        Route::post('pages/{page}/unpublish', [PageController::class, 'unpublish'])->name('api.settings.pages.unpublish');
        Route::post('pages/{page}/duplicate', [PageController::class, 'duplicate'])->name('api.settings.pages.duplicate');

        // Auto-save routes
        Route::patch('pages/{page}/auto-save', [PageAutoSaveController::class, 'save'])->name('api.pages.auto-save');
        Route::get('pages/{page}/auto-save', [PageAutoSaveController::class, 'getDraft'])->name('api.pages.auto-save.get');
        Route::post('pages/{page}/auto-save/restore', [PageAutoSaveController::class, 'restore'])->name('api.pages.auto-save.restore');
        Route::delete('pages/{page}/auto-save', [PageAutoSaveController::class, 'discard'])->name('api.pages.auto-save.discard');

        // Page lock routes
        Route::get('pages/{page}/lock', [PageLockController::class, 'check'])->name('api.pages.lock.check');
        Route::post('pages/{page}/lock', [PageLockController::class, 'acquire'])->name('api.pages.lock.acquire');
        Route::patch('pages/{page}/lock', [PageLockController::class, 'renew'])->name('api.pages.lock.renew');
        Route::delete('pages/{page}/lock', [PageLockController::class, 'release'])->name('api.pages.lock.release');

        // Cache routes
        Route::prefix('cache')->name('cache.')->group(function () {
            Route::get('stats', [PageCacheController::class, 'stats'])->name('stats');
            Route::post('warm', [PageCacheController::class, 'warmAll'])->name('warm');
            Route::post('clear', [PageCacheController::class, 'clear'])->name('clear');
            Route::get('audits', [PageCacheController::class, 'audits'])->name('audits');
        });
    });

});
