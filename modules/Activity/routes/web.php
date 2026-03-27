<?php

use Illuminate\Support\Facades\Route;
use Modules\Activity\Http\Controllers\ActivityController;

// Activity routes
Route::middleware(['web', 'auth'])
    ->prefix('activity')
    ->name('activity.')
    ->group(function () {
        Route::get('/logs', [ActivityController::class, 'logs'])->name('logs');
        Route::get('/logs/export', [ActivityController::class, 'export'])->name('export')->middleware('throttle:10,1');
        Route::get('/audit', [ActivityController::class, 'audit'])->name('audit');
        Route::get('/audit/data', [ActivityController::class, 'auditData'])->name('audit.data');
    });
