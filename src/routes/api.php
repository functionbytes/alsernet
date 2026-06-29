<?php

use App\Http\Api\V1\Controllers\MeController;
use Illuminate\Support\Facades\Route;
use Modules\Backup\Http\Controllers\Api\BackupScheduleApiController;
use Modules\Health\Http\Controllers\Api\HealthController;
use Modules\Page\Http\Controllers\PublicController;
use Modules\Reviews\Http\Controllers\Api\ReviewController;
use Modules\User\Http\Controllers\Api\UserApiController;

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
        Route::get('health', [HealthController::class, 'health'])->name('health.status');

        // Public pages listing
        Route::get('pages', [PublicController::class, 'index'])
            ->name('pages.index')
            ->middleware('throttle:api-strict');

        // Mobile API — authenticated customer (Sanctum + audience guard)
        Route::middleware(['auth:sanctum', 'customer', 'throttle:api-mobile'])->group(function () {
            Route::get('me', [MeController::class, 'me'])->name('me');
            Route::put('me', [MeController::class, 'updateProfile'])->name('me.update');
            Route::get('me/modules', [MeController::class, 'modules'])->name('me.modules');
        });

        // Authenticated admin endpoints (existing API for panel)
        Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {

            // Admin profile (renamed from `me` to avoid clashing with the mobile customer `me` endpoint)
            Route::get('admin/me', [UserApiController::class, 'me'])->name('admin.me');

            // Reviews (only when module is enabled)
            if (app('modules')->isEnabled('Reviews')) {
                Route::get('reviews', [ReviewController::class, 'index'])->name('reviews.index');
                Route::get('reviews/stats', [ReviewController::class, 'stats'])->name('reviews.stats');
            }

            // Backup schedules (read-only)
            Route::middleware('can:Backup.schedules.index')->group(function () {
                Route::get('backup/schedules', [BackupScheduleApiController::class, 'index'])->name('backup.schedules.index');
                Route::get('backup/schedules/{schedule}', [BackupScheduleApiController::class, 'show'])->name('backup.schedules.show');
            });
        });
    });
