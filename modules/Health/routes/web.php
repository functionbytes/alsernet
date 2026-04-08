<?php

use Illuminate\Support\Facades\Route;
use Modules\Health\Http\Controllers\HealthController;

/*
|--------------------------------------------------------------------------
| Health Routes
|--------------------------------------------------------------------------
|
| Health monitoring and system diagnostics routes
| Prefix: /backups/health (applied by ServiceProvider)
| Name: backups.health.* (applied by ServiceProvider)
| Middleware: web, auth, settings
|
*/

Route::middleware(['web', 'auth', 'settings'])
    ->prefix('panel/setting/health')
    ->name('settings.health.')
    ->group(function () {
        Route::get('/', [HealthController::class, 'index'])->name('index');
        Route::get('/check', [HealthController::class, 'check'])->name('check');
        Route::get('/history', [HealthController::class, 'history'])->name('history');
        Route::delete('/history/{id}', [HealthController::class, 'destroyHistoryRecord'])->name('history.destroy');
        Route::post('/history/bulk', [HealthController::class, 'bulkDestroyHistory'])->name('history.bulk');

        // System management actions
        Route::post('/schedule/run', [HealthController::class, 'runSchedule'])->name('schedule.run');
        Route::get('/schedule/list', [HealthController::class, 'scheduleList'])->name('schedule.list');
        Route::get('/queue/status', [HealthController::class, 'queueStatus'])->name('queue.status');
        Route::post('/queue/process', [HealthController::class, 'processQueue'])->name('queue.process');

        // Supervisor configuration
        Route::post('/supervisor/generate', [HealthController::class, 'generateSupervisorConfig'])->name('supervisor.generate');
        Route::get('/supervisor/download', [HealthController::class, 'downloadSupervisorConfig'])->name('supervisor.download');
    });

// Health Check API Routes (rate limited, no authentication - for external monitoring)
Route::prefix('api/health')->middleware(['throttle:30,1'])->group(function () {
    Route::get('ping', [HealthController::class, 'ping']);
    Route::get('/', [HealthController::class, 'health']);
    Route::get('detailed', [HealthController::class, 'detailed']);
});
