<?php

use Illuminate\Support\Facades\Route;
use Modules\Horizon\Http\Controllers\Api\HorizonApiController;
use Modules\Horizon\Http\Controllers\HorizonController;

/*
|--------------------------------------------------------------------------
| Horizon Routes
|--------------------------------------------------------------------------
|
| Queue monitoring and management dashboard
| Prefix: /backups/horizon (applied by ServiceProvider)
| Name: backups.horizon.* (applied by ServiceProvider)
| Middleware: web, auth, role:super-admin
|
*/

Route::middleware(['web', 'auth', 'role:super-admin'])
    ->prefix('settings/horizon')
    ->name('settings.horizon.')
    ->group(function () {
        Route::get('/', [HorizonController::class, 'index'])->name('index');
        Route::get('/jobs', [HorizonController::class, 'jobs'])->name('jobs');
        Route::get('/failed', [HorizonController::class, 'failed'])->name('failed');
        Route::get('/metrics', [HorizonController::class, 'metrics'])->name('metrics');

        // API routes for AJAX calls
        Route::prefix('api')->name('api.')->group(function () {
            Route::get('/stats', [HorizonApiController::class, 'stats'])->name('stats');
            Route::get('/recent-jobs', [HorizonApiController::class, 'recentJobs'])->name('recentJobs');
            Route::get('/failed-jobs', [HorizonApiController::class, 'failedJobs'])->name('failedJobs');
            Route::post('/retry-job/{jobId}', [HorizonApiController::class, 'retryJob'])->name('retryJob');
            Route::post('/retry-all', [HorizonApiController::class, 'retryAll'])->name('retryAll');
            Route::post('/forget-job/{jobId}', [HorizonApiController::class, 'forgetJob'])->name('forgetJob');
        });
    });
