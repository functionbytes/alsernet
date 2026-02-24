<?php

use Illuminate\Support\Facades\Route;
use Modules\Reviews\Http\Controllers\Api\ReviewController;

Route::middleware(['api', 'auth:sanctum', 'throttle:60,1'])
    ->prefix('reviews')
    ->name('api.reviews.')
    ->group(function () {
        Route::get('/', [ReviewController::class, 'index'])->name('index');
        Route::get('stats', [ReviewController::class, 'stats'])->name('stats');
        Route::get('{review}', [ReviewController::class, 'show'])->name('show');
        Route::get('{review}/suggestions', [ReviewController::class, 'suggestions'])->name('suggestions');
    });
