<?php

use Illuminate\Support\Facades\Route;
use Modules\Analytics\Http\Controllers\AnalyticsController;
use Modules\Analytics\Http\Controllers\DashboardController;
use Modules\Analytics\Http\Controllers\Settings\AnalyticsNotificationController;
use Modules\Analytics\Http\Controllers\Settings\AnalyticsReportScheduleController;
use Modules\Analytics\Http\Controllers\Settings\AnalyticsSettingController;
use Modules\Analytics\Http\Controllers\Settings\AnalyticsSettingJsonController;

Route::middleware(['web', 'module:Analytics'])->group(function () {

    // Settings routes
    Route::middleware(['auth'])
        ->prefix('panel/settings/analytics')
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

            // Notification settings
            Route::get('/notifications', [AnalyticsNotificationController::class, 'index'])->name('notifications');
            Route::post('/notifications', [AnalyticsNotificationController::class, 'update'])->name('notifications.update');

            // Scheduled reports
            Route::prefix('schedules')->name('schedules.')->group(function () {
                Route::get('/', [AnalyticsReportScheduleController::class, 'index'])->name('index');
                Route::get('/create', [AnalyticsReportScheduleController::class, 'create'])->name('create');
                Route::post('/', [AnalyticsReportScheduleController::class, 'store'])->name('store');
                Route::get('/{schedule}/edit', [AnalyticsReportScheduleController::class, 'edit'])->name('edit');
                Route::put('/{schedule}', [AnalyticsReportScheduleController::class, 'update'])->name('update');
                Route::post('/bulk-action', [AnalyticsReportScheduleController::class, 'bulkAction'])->name('bulk-action');
                Route::delete('/{schedule}', [AnalyticsReportScheduleController::class, 'destroy'])->name('destroy');
                Route::post('/{schedule}/toggle', [AnalyticsReportScheduleController::class, 'toggle'])->name('toggle');
            });
        });

    // Dashboard routes
    Route::middleware(['auth'])
        ->prefix('panel/analytics')
        ->name('analytics.')
        ->group(function () {
            Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        });

    // Analytics data endpoints (for widgets/dashboard)
    Route::middleware(['auth', 'throttle:'.config('analytics.api.throttle_per_minute', 60).',1'])
        ->prefix('api/analytics')
        ->name('api.analytics.')
        ->group(function () {
            Route::get('/overview', [AnalyticsController::class, 'overview'])->name('overview');
            Route::get('/top-pages', [AnalyticsController::class, 'topPages'])->name('top-pages');
            Route::get('/top-browsers', [AnalyticsController::class, 'topBrowsers'])->name('top-browsers');
            Route::get('/top-referrers', [AnalyticsController::class, 'topReferrers'])->name('top-referrers');
            Route::get('/query', [AnalyticsController::class, 'query'])->name('query');
            Route::get('/top-countries', [AnalyticsController::class, 'topCountries'])->name('top-countries');
            Route::get('/device-categories', [AnalyticsController::class, 'deviceCategories'])->name('device-categories');
            Route::get('/operating-systems', [AnalyticsController::class, 'operatingSystems'])->name('operating-systems');
            Route::get('/traffic-sources', [AnalyticsController::class, 'trafficSources'])->name('traffic-sources');
            Route::get('/session-metrics', [AnalyticsController::class, 'sessionMetrics'])->name('session-metrics');
            Route::get('/realtime', [AnalyticsController::class, 'realtimeUsers'])->name('realtime');
            Route::get('/landing-pages', [AnalyticsController::class, 'landingPages'])->name('landing-pages');
            Route::get('/exit-pages', [AnalyticsController::class, 'exitPages'])->name('exit-pages');
            Route::get('/channel-trend', [AnalyticsController::class, 'channelTrend'])->name('channel-trend');
            Route::get('/hourly-heatmap', [AnalyticsController::class, 'hourlyHeatmap'])->name('hourly-heatmap');
            Route::get('/comparison', [AnalyticsController::class, 'comparison'])->name('comparison');
            Route::get('/search-terms', [AnalyticsController::class, 'searchTerms'])->name('search-terms');
            Route::get('/user-flow', [AnalyticsController::class, 'userFlow'])->name('user-flow');
            Route::post('/trigger-report', [AnalyticsController::class, 'triggerReport'])->name('trigger-report');
            Route::get('/dashboard-widget', [AnalyticsController::class, 'dashboardWidget'])->name('dashboard-widget');
        });

});

// Legacy redirects (singular → plural). TODO remove after 2026-12-31
Route::middleware(['web'])->group(function () {
    Route::redirect('panel/setting/analytics/{any}', 'panel/settings/analytics/{any}', 301)->where('any', '.*');
    Route::redirect('panel/setting/analytics', 'panel/settings/analytics', 301);
});
