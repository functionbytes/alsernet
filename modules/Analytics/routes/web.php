<?php

use Illuminate\Support\Facades\Route;
use Modules\Analytics\Http\Controllers\AnalyticsController;
use Modules\Analytics\Http\Controllers\AnalyticsSettingsController;
use Modules\Analytics\Http\Controllers\DashboardController;

Route::middleware(['web', 'module:Analytics'])->group(function () {

    // Settings routes
    Route::middleware(['web', 'auth'])
        ->prefix('settings/analytics')
        ->name('settings.analytics.')
        ->group(function () {
            Route::get('/', [AnalyticsSettingsController::class, 'index'])->name('index');
            Route::put('/', [AnalyticsSettingsController::class, 'update'])->name('update');
            Route::post('/validate-credentials', [AnalyticsSettingsController::class, 'validateCredentials'])->name('validate-credentials');
        });

    // Dashboard routes
    Route::middleware(['web', 'auth'])
        ->prefix('analytics')
        ->name('analytics.')
        ->group(function () {
            Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        });

    // Analytics data endpoints (for widgets/dashboard)
    Route::middleware(['web', 'auth'])
        ->prefix('api/analytics')
        ->name('api.analytics.')
        ->group(function () {
            Route::get('/overview', [AnalyticsController::class, 'overview'])->name('overview');
            Route::get('/top-pages', [AnalyticsController::class, 'topPages'])->name('top-pages');
            Route::get('/top-browsers', [AnalyticsController::class, 'topBrowsers'])->name('top-browsers');
            Route::get('/top-referrers', [AnalyticsController::class, 'topReferrers'])->name('top-referrers');
            Route::get('/query', [AnalyticsController::class, 'query'])->name('query');
        });

});
