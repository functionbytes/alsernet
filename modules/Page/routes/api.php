<?php

use Illuminate\Support\Facades\Route;
use Modules\Page\Http\Controllers\Api\PageAutoSaveController;
use Modules\Page\Http\Controllers\Api\PageCacheController;
use Modules\Page\Http\Controllers\Api\PageLockController;
use Modules\Page\Http\Controllers\PageController;
use Modules\Page\Http\Controllers\PageTrackingController;
use Modules\Page\Http\Controllers\PublicController;
use Modules\Page\Http\Controllers\SearchController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // Time-on-page tracking (public, no auth)
    Route::post('pages/track-time', [PageTrackingController::class, 'trackTime'])
        ->middleware('throttle:30,1')
        ->name('pages.track-time');

    // Static public routes (search must be registered before the {slug} wildcard)
    Route::middleware(['throttle:public-pages'])->group(function () {
        Route::get('pages', [PublicController::class, 'index'])->name('pages.index');
    });

    Route::middleware(['throttle:public-pages-search'])->group(function () {
        Route::get('pages/search', [SearchController::class, 'search'])->name('pages.search');
        Route::get('pages/search/quick', [SearchController::class, 'quickSearch'])->name('pages.search.quick');
    });

    Route::middleware(['throttle:public-pages'])->group(function () {
        Route::get('pages/{slug}', [PublicController::class, 'show'])->name('pages.show');
    });

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::apiResource('pages', PageController::class)->names('settings.pages');
        Route::post('pages/{page}/publish', [PageController::class, 'publish'])->name('settings.pages.publish');
        Route::post('pages/{page}/unpublish', [PageController::class, 'unpublish'])->name('settings.pages.unpublish');
        Route::post('pages/{page}/duplicate', [PageController::class, 'duplicate'])->name('settings.pages.duplicate');

        // Auto-save routes
        Route::patch('pages/{page}/auto-save', [PageAutoSaveController::class, 'save'])->name('pages.auto-save');
        Route::get('pages/{page}/auto-save', [PageAutoSaveController::class, 'getDraft'])->name('pages.auto-save.get');
        Route::post('pages/{page}/auto-save/restore', [PageAutoSaveController::class, 'restore'])->name('pages.auto-save.restore');
        Route::delete('pages/{page}/auto-save', [PageAutoSaveController::class, 'discard'])->name('pages.auto-save.discard');

        // Page lock routes
        Route::get('pages/{page}/lock', [PageLockController::class, 'check'])->name('pages.lock.check');
        Route::post('pages/{page}/lock', [PageLockController::class, 'acquire'])->name('pages.lock.acquire');
        Route::patch('pages/{page}/lock', [PageLockController::class, 'renew'])->name('pages.lock.renew');
        Route::delete('pages/{page}/lock', [PageLockController::class, 'release'])->name('pages.lock.release');

        // Cache routes
        Route::prefix('cache')->name('v1.cache.')->group(function () {
            Route::get('stats', [PageCacheController::class, 'stats'])->name('stats');
            Route::post('warm', [PageCacheController::class, 'warmAll'])->name('warm');
            Route::post('clear', [PageCacheController::class, 'clear'])->name('clear');
            Route::get('audits', [PageCacheController::class, 'audits'])->name('audits');
        });
    });

});
