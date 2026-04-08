<?php

use App\Http\Controllers\Api\BackupScheduleController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;
use Modules\Page\Http\Controllers\PublicController;
use Modules\Reviews\Http\Controllers\Api\ReviewController;

/*
|--------------------------------------------------------------------------
| API Routes — v1
|--------------------------------------------------------------------------
*/

Route::prefix('v1')
    ->name('api.v1.')
    ->middleware('api.version:v1')
    ->group(function () {

        // Health checks (public)
        Route::get('ping', [HealthController::class, 'ping'])->name('health.ping');
        Route::get('health', [HealthController::class, 'status'])->name('health.status');

        // Public pages listing
        Route::get('pages', [PublicController::class, 'index'])
            ->name('pages.index')
            ->middleware('throttle:api-strict');

        // Authenticated endpoints
        Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {

            // Current user profile
            Route::get('me', [UserController::class, 'me'])->name('user.me');

            // Reviews
            Route::get('reviews', [ReviewController::class, 'index'])->name('reviews.index');
            Route::get('reviews/stats', [ReviewController::class, 'stats'])->name('reviews.stats');

            // Backup schedules (read-only)
            Route::middleware('can:Backup.schedules.index')->group(function () {
                Route::get('backup/schedules', [BackupScheduleController::class, 'index'])->name('backup.schedules.index');
                Route::get('backup/schedules/{schedule}', [BackupScheduleController::class, 'show'])->name('backup.schedules.show');
            });
        });
    });
