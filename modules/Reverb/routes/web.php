<?php

use Illuminate\Support\Facades\Route;
use Modules\Reverb\Http\Controllers\ReverbSettingsController;
use Modules\Reverb\Http\Controllers\ReverbMonitoringController;

/*
|--------------------------------------------------------------------------
| Reverb Module Web Routes
|--------------------------------------------------------------------------
|
| Prefix: /backups/reverb (applied by ServiceProvider)
| Name: manager.backups.reverb.* (applied by ServiceProvider)
|
| Admin panel routes for Reverb configuration and monitoring
*/

// Settings
Route::middleware('can:reverb.backups.view')->group(function () {
    Route::get('/', [ReverbSettingsController::class, 'index'])->name('index');
});

Route::middleware('can:reverb.backups.update')->group(function () {
    Route::get('/edit', [ReverbSettingsController::class, 'edit'])->name('edit');
    Route::put('/update', [ReverbSettingsController::class, 'update'])->name('update');
    Route::post('/test-connection', [ReverbSettingsController::class, 'testConnection'])->name('test-connection');
});

// Monitoring
Route::middleware('can:reverb.monitoring.view')->group(function () {
    Route::get('/monitoring', [ReverbMonitoringController::class, 'index'])->name('monitoring.index');
    Route::get('/monitoring/stats', [ReverbMonitoringController::class, 'getStats'])->name('monitoring.stats');
    Route::get('/monitoring/channels', [ReverbMonitoringController::class, 'getChannels'])->name('monitoring.channels');
    Route::get('/monitoring/connections', [ReverbMonitoringController::class, 'getConnections'])->name('monitoring.connections');
});
