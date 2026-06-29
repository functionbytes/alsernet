<?php

use Illuminate\Support\Facades\Route;
use Modules\Engagement\Http\Controllers\Managers\AnalyticsController;
use Modules\Engagement\Http\Controllers\Managers\CustomerProfileController;
use Modules\Engagement\Http\Controllers\Managers\ExportController;
use Modules\Engagement\Http\Controllers\Managers\IntegrationLookupController;
use Modules\Engagement\Http\Controllers\Managers\LiveVisitorsController;

/*
|--------------------------------------------------------------------------
| Engagement manager routes
|--------------------------------------------------------------------------
| Mounted with prefix `panel/engagement` and name `manager.engagement.`
*/

Route::prefix('live-visitors')->name('live-visitors.')->group(function () {
    Route::get('/', [LiveVisitorsController::class, 'index'])->name('index');
    Route::get('data', [LiveVisitorsController::class, 'data'])->name('data')->middleware('throttle:120,1');
});

Route::prefix('analytics')->name('analytics.')->group(function () {
    Route::get('/', [AnalyticsController::class, 'page'])->name('page');
    Route::get('overview', [AnalyticsController::class, 'overview'])->name('overview');
    Route::get('events-by-day', [AnalyticsController::class, 'eventsByDay'])->name('events-by-day');
    Route::get('segments', [AnalyticsController::class, 'segmentDistribution'])->name('segments');
    Route::get('top-events', [AnalyticsController::class, 'topEvents'])->name('top-events');
    Route::get('triggers', [AnalyticsController::class, 'triggerPerformance'])->name('triggers');
});

Route::prefix('export')->name('export.')->group(function () {
    Route::get('events', [ExportController::class, 'events'])->name('events');
    Route::get('scores', [ExportController::class, 'scores'])->name('scores');
});

Route::prefix('customer-profile')->name('customer-profile.')->group(function () {
    Route::get('/{customer}', [CustomerProfileController::class, 'show'])->name('show');
    Route::get('/{customer}/data', [CustomerProfileController::class, 'data'])->name('data');
});

Route::post('customer-data/lookup', IntegrationLookupController::class)
    ->name('customer-data.lookup')
    ->middleware('throttle:60,1');
