<?php

use Illuminate\Support\Facades\Route;
use Modules\Activity\Http\Controllers\ActivityController;

// Activity routes
Route::middleware(['web', 'auth'])
    ->prefix('activity')
    ->name('activity.')
    ->group(function () {
        Route::get('/logs', [ActivityController::class, 'logs'])->name('logs');
        Route::get('/audit', [ActivityController::class, 'audit'])->name('audit');
    });
