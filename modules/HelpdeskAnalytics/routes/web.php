<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskAnalytics\Http\Controllers\Managers\AnalyticsController;
use Modules\HelpdeskAnalytics\Http\Controllers\Managers\ChatSalesController;

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
        // Live commerce: ventas atribuidas al chat (importes → permiso propio).
        Route::get('chat-sales', [ChatSalesController::class, 'index'])
            ->middleware('can:helpdeskanalytics.chat-sales')
            ->name('chat-sales');
    });
