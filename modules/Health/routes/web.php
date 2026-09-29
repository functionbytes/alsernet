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

// Health Check API Routes (sin autenticación, para monitorización externa).
// 29-sep-2026: con throttle por IP y respuestas cacheadas/mínimas. La tienda
// (213.134.40.100) consulta /documents a menudo: por eso su límite es más alto.
Route::prefix('api/health')->group(function () {
    Route::get('ping', [HealthController::class, 'ping'])->middleware('throttle:120,1');            // Ping simple
    Route::get('/', [HealthController::class, 'health'])->middleware('throttle:30,1');              // Estado global (mínimo)
    Route::get('documents', [HealthController::class, 'documentsHealth'])->middleware('throttle:120,1'); // Health específico documentos
    Route::get('detailed', [HealthController::class, 'detailed'])->middleware('throttle:30,1');     // Detallado (token)
});
