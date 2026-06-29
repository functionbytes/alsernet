<?php

use Illuminate\Support\Facades\Route;
use Modules\System\Http\Controllers\Settings\MantenanceSettingsController;
use Modules\System\Http\Controllers\Settings\ServerAccessController;
use Modules\System\Http\Controllers\Settings\SupervisorController;
use Modules\System\Http\Controllers\Settings\SystemCacheController;
use Modules\System\Http\Controllers\Settings\SystemSettingsController;
use Modules\System\Http\Controllers\Settings\UploadingSettingsController;
use Modules\System\Http\Controllers\SystemInfoController;

/*
|--------------------------------------------------------------------------
| System Module Routes
|--------------------------------------------------------------------------
|
| System configuration, cache management, and system information
| Prefix: /panel/settings/system
| Name: backups.system.* (applied by ServiceProvider)
| Middleware: web, auth, settings
|
*/

Route::middleware(['web', 'auth', 'settings'])
    ->prefix('panel/settings/system')
    ->name('settings.system.')
    ->group(function () {
        // System Settings — read-only
        Route::get('/', [SystemSettingsController::class, 'index'])->name('index');
        Route::get('/queue-stats', [SystemSettingsController::class, 'queueStats'])->name('queue-stats');
        Route::get('/disk-stats', [SystemSettingsController::class, 'diskStats'])->name('disk-stats');

        // System Information — read-only
        Route::prefix('info')->name('info.')->group(function () {
            Route::get('/', [SystemInfoController::class, 'index'])->name('index');
            Route::get('/api', [SystemInfoController::class, 'api'])->name('api');
        });

        // System Cache — read-only
        Route::prefix('cache')->name('cache.')->group(function () {
            Route::get('/', [SystemCacheController::class, 'index'])->name('index');
            Route::get('/debug', [SystemCacheController::class, 'debug'])->name('debug');
        });

        // Server Access & Logs — read-only
        Route::prefix('access')->name('access.')->group(function () {
            Route::get('/', [ServerAccessController::class, 'index'])->name('index');
            Route::get('/stats', [ServerAccessController::class, 'stats'])->name('stats');
            Route::get('/download', [ServerAccessController::class, 'downloadLogs'])->name('download');
        });

        // Supervisor — read-only
        Route::prefix('supervisor')->name('supervisor.')->group(function () {
            Route::get('/', [SupervisorController::class, 'index'])->name('index');
            Route::get('/status/ajax', [SupervisorController::class, 'getStatusAjax'])->name('status-ajax');
            Route::get('/api/scheduled-jobs', [SupervisorController::class, 'getScheduledJobs'])->name('scheduled-jobs');
            Route::get('/api/list-commands', [SupervisorController::class, 'listCommands'])->name('list-commands');
            Route::get('/backups/list', [SupervisorController::class, 'listBackups'])->name('backups-list');
            Route::get('/backups/{backupId}/download', [SupervisorController::class, 'downloadBackup'])->name('backup-download');
            Route::get('/config-files/list', [SupervisorController::class, 'listConfigFiles'])->name('config-files-list');
            Route::get('/config-files/get', [SupervisorController::class, 'getConfigFile'])->name('config-file-get');
            Route::get('/{processName}', [SupervisorController::class, 'show'])->name('show');
            Route::get('/{processName}/logs', [SupervisorController::class, 'getLogs'])->name('logs');
        });

        // Uploading Settings — read-only
        Route::prefix('uploading')->name('uploading.')->group(function () {
            Route::get('/', [UploadingSettingsController::class, 'index'])->name('index');
        });

        // Maintenance Mode — read-only
        Route::prefix('maintenance')->name('maintenance.')->group(function () {
            Route::get('/', [MantenanceSettingsController::class, 'index'])->name('index');
        });

        // ---------------------------------------------------------------
        // Write / destructive routes — require elevated role
        // ---------------------------------------------------------------
        Route::middleware(['role:super-settings|administrative|manager'])
            ->group(function () {
                // Queue writes
                Route::put('/queue/update', [SystemSettingsController::class, 'updateQueue'])->name('queue.update');
                Route::post('/queue/test', [SystemSettingsController::class, 'testQueue'])->name('queue.test');
                Route::post('/queue/restart', [SystemSettingsController::class, 'restartQueue'])->name('queue.restart');
                Route::put('/websockets/update', [SystemSettingsController::class, 'updateWebsockets'])->name('websockets.update');
                Route::delete('/failed-jobs/{id}', [SystemSettingsController::class, 'deleteFailedJob'])->name('failed-jobs.delete');
                Route::post('/retry-failed-job/{id}', [SystemSettingsController::class, 'retryFailedJob'])->name('retry-failed-job');

                // Cache & maintenance writes
                Route::prefix('cache')->name('cache.')->group(function () {
                    Route::post('/clear-cache', [SystemCacheController::class, 'clearCache'])->name('clear-cache');
                    Route::post('/clear-config-cache', [SystemCacheController::class, 'clearConfigCache'])->name('clear-config-cache');
                    Route::post('/cache-config', [SystemCacheController::class, 'cacheConfig'])->name('cache-config');
                    Route::post('/clear-route-cache', [SystemCacheController::class, 'clearRouteCache'])->name('clear-route-cache');
                    Route::post('/clear-view-cache', [SystemCacheController::class, 'clearViewCache'])->name('clear-view-cache');
                    Route::post('/clear-optimization', [SystemCacheController::class, 'clearOptimization'])->name('clear-optimization');
                    Route::post('/composer-dump-autoload', [SystemCacheController::class, 'composerDumpAutoload'])->name('composer-dump-autoload');
                    Route::post('/execute-all', [SystemCacheController::class, 'executeAll'])->name('execute-all');
                });

                // Access log writes
                Route::prefix('access')->name('access.')->group(function () {
                    Route::post('/clear', [ServerAccessController::class, 'clearLogs'])->name('clear');
                    Route::post('/bulk-action', [ServerAccessController::class, 'bulkAction'])->name('bulk-action');
                });

                // Supervisor writes
                Route::prefix('supervisor')->name('supervisor.')->group(function () {
                    Route::post('/{processName}/start', [SupervisorController::class, 'start'])->name('start');
                    Route::post('/{processName}/stop', [SupervisorController::class, 'stop'])->name('stop');
                    Route::post('/{processName}/restart', [SupervisorController::class, 'restart'])->name('restart');
                    Route::post('/reload', [SupervisorController::class, 'reload'])->name('reload');
                    Route::post('/restart-service', [SupervisorController::class, 'restartSupervisor'])->name('restart-service');
                    Route::post('/restart-all', [SupervisorController::class, 'restartAll'])->name('restart-all');
                    Route::post('/api/run-scheduler', [SupervisorController::class, 'runScheduler'])->name('run-scheduler');
                    Route::post('/api/run-command', [SupervisorController::class, 'runCommand'])->name('run-command');
                    Route::post('/backups/create', [SupervisorController::class, 'createBackup'])->name('backup-create');
                    Route::post('/backups/{backupId}/restore', [SupervisorController::class, 'restoreBackup'])->name('backup-restore');
                    Route::delete('/backups/{backupId}/delete', [SupervisorController::class, 'deleteBackup'])->name('backup-delete');
                    Route::post('/config-files/update', [SupervisorController::class, 'updateConfigFile'])->name('config-file-update');
                });

                // Uploading settings write
                Route::prefix('uploading')->name('uploading.')->group(function () {
                    Route::put('/update', [UploadingSettingsController::class, 'update'])->name('update');
                });

                // Maintenance mode write
                Route::prefix('maintenance')->name('maintenance.')->group(function () {
                    Route::post('/update', [MantenanceSettingsController::class, 'update'])->name('update');
                });
            });
    });

// Legacy redirects (singular → plural). TODO remove after 2026-12-31
Route::middleware(['web'])->group(function () {
    Route::redirect('panel/setting/system/{any}', 'panel/settings/system/{any}', 301)->where('any', '.*');
    Route::redirect('panel/setting/system', 'panel/settings/system', 301);
});
