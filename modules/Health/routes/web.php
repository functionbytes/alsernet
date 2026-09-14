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
| Middleware: web, auth, role:super-admin
|
*/

Route::middleware(['web', 'auth', 'role:super-admin'])
    ->prefix('settings/health')
    ->name('settings.health.')
    ->group(function () {
        Route::get('/', [HealthController::class, 'index'])->name('index');
        Route::get('/check', [HealthController::class, 'check'])->name('check');
        Route::get('/history', [HealthController::class, 'history'])->name('history');

        // System management actions
        Route::post('/schedule/run', [HealthController::class, 'runSchedule'])->name('schedule.run');
        Route::get('/schedule/list', [HealthController::class, 'scheduleList'])->name('schedule.list');
        Route::get('/queue/status', [HealthController::class, 'queueStatus'])->name('queue.status');
        Route::post('/queue/process', [HealthController::class, 'processQueue'])->name('queue.process');

        // Supervisor configuration
        Route::post('/supervisor/generate', [HealthController::class, 'generateSupervisorConfig'])->name('supervisor.generate');
        Route::get('/supervisor/download', [HealthController::class, 'downloadSupervisorConfig'])->name('supervisor.download');
    });

// Health Check API Routes (no authentication, no rate limiting - for external monitoring)
Route::prefix('api/health')->group(function () {
    Route::get('ping', [HealthController::class, 'ping']);           // Ping simple
    Route::get('/', [HealthController::class, 'health']);            // Health check completo
    Route::get('documents', [HealthController::class, 'documentsHealth']); // Health específico documentos
    Route::get('detailed', [HealthController::class, 'detailed']);   // Detallado (solo debug)
});
