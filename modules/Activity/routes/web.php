<?php

use Illuminate\Support\Facades\Route;
use Modules\Activity\Http\Controllers\ActivityController;

// Activity routes
Route::middleware(['web', 'auth'])
    ->prefix('panel/activity')
    ->name('activity.')
    ->group(function () {
        Route::get('/logs', [ActivityController::class, 'logs'])->name('logs');
        Route::post('/logs/bulk-action', [ActivityController::class, 'bulkAction'])->name('logs.bulk-action');
        Route::get('/logs/export', [ActivityController::class, 'export'])->name('export')->middleware('throttle:10,1');
        Route::get('/logs/{id}', [ActivityController::class, 'show'])->name('logs.show');
        Route::get('/audit', [ActivityController::class, 'audit'])->name('audit');
        Route::get('/audit/data', [ActivityController::class, 'auditData'])->name('audit.data');
    });
