<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskAnalytics\Http\Controllers\Managers\AnalyticsController;

/*
| HelpdeskAnalytics manager routes.
|
| Mounted by HelpdeskAnalyticsServiceProvider with prefix 'panel/helpdeskanalytics'
| and middleware ['web', 'auth'].
*/
Route::name('helpdeskanalytics.')
    ->middleware('can:helpdeskanalytics.view')
    ->group(function () {
        Route::get('/', [AnalyticsController::class, 'index'])->name('index');
        Route::get('data', [AnalyticsController::class, 'data'])->name('data');
    });
