<?php

use Illuminate\Support\Facades\Route;
use Modules\Analytics\Http\Controllers\AnalyticsController;
use Modules\Analytics\Http\Controllers\DashboardController;
use Modules\Analytics\Http\Controllers\Settings\AnalyticsSettingController;
use Modules\Analytics\Http\Controllers\Settings\AnalyticsSettingJsonController;

Route::middleware(['web', 'module:Analytics'])->group(function () {

    // Settings routes
    Route::middleware(['web', 'auth'])
        ->prefix('setting/analytics')
        ->name('settings.analytics.')
        ->group(function () {
            Route::get('/', [AnalyticsSettingController::class, 'index'])->name('index');
            Route::put('/', [AnalyticsSettingController::class, 'update'])->name('update');
            Route::post('/validate-credentials', [AnalyticsSettingController::class, 'validateCredentials'])->name('validate-credentials');
            Route::post('/test-connection', [AnalyticsSettingController::class, 'testConnection'])->name('test-connection');
            Route::post('/clear-cache', [AnalyticsSettingController::class, 'clearCache'])->name('clear-cache');

            // JSON upload routes
            Route::post('/upload-json', [AnalyticsSettingJsonController::class, 'upload'])->name('upload-json');
            Route::post('/validate-json', [AnalyticsSettingJsonController::class, 'validateJson'])->name('validate-json');
            Route::get('/download-template', [AnalyticsSettingJsonController::class, 'downloadTemplate'])->name('download-template');
            Route::post('/format-json', [AnalyticsSettingJsonController::class, 'formatJson'])->name('format-json');
            Route::post('/extract-info', [AnalyticsSettingJsonController::class, 'extractInfo'])->name('extract-info');
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
