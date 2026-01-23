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
| Prefix: /backups/system (applied by ServiceProvider)
| Name: backups.system.* (applied by ServiceProvider)
| Middleware: web, auth, role:super-admin
|
*/

Route::middleware(['web', 'auth', 'role:super-admin'])
    ->prefix('settings/system')
    ->name('settings.system.')
    ->group(function () {
        // System Settings
        Route::get('/', [SystemSettingsController::class, 'index'])->name('index');
        Route::put('/queue/update', [SystemSettingsController::class, 'updateQueue'])->name('queue.update');
        Route::post('/queue/test', [SystemSettingsController::class, 'testQueue'])->name('queue.test');
        Route::post('/queue/restart', [SystemSettingsController::class, 'restartQueue'])->name('queue.restart');
        Route::put('/websockets/update', [SystemSettingsController::class, 'updateWebsockets'])->name('websockets.update');

        // System Information
        Route::prefix('info')->name('info.')->group(function () {
            Route::get('/', [SystemInfoController::class, 'index'])->name('index');
            Route::get('/api', [SystemInfoController::class, 'api'])->name('api');
        });

        // System Cache & Maintenance
        Route::prefix('cache')->name('cache.')->group(function () {
            Route::get('/', [SystemCacheController::class, 'index'])->name('index');
            Route::get('/debug', [SystemCacheController::class, 'debug'])->name('debug');
            Route::post('/clear-cache', [SystemCacheController::class, 'clearCache'])->name('clear-cache');
            Route::post('/clear-config-cache', [SystemCacheController::class, 'clearConfigCache'])->name('clear-config-cache');
            Route::post('/cache-config', [SystemCacheController::class, 'cacheConfig'])->name('cache-config');
            Route::post('/clear-route-cache', [SystemCacheController::class, 'clearRouteCache'])->name('clear-route-cache');
            Route::post('/clear-view-cache', [SystemCacheController::class, 'clearViewCache'])->name('clear-view-cache');
            Route::post('/clear-optimization', [SystemCacheController::class, 'clearOptimization'])->name('clear-optimization');
            Route::post('/composer-dump-autoload', [SystemCacheController::class, 'composerDumpAutoload'])->name('composer-dump-autoload');
            Route::post('/execute-all', [SystemCacheController::class, 'executeAll'])->name('execute-all');
        });

        // Server Access & Logs
        Route::prefix('access')->name('access.')->group(function () {
            Route::get('/', [ServerAccessController::class, 'index'])->name('index');
            Route::get('/stats', [ServerAccessController::class, 'stats'])->name('stats');
            Route::post('/clear', [ServerAccessController::class, 'clearLogs'])->name('clear');
            Route::get('/download', [ServerAccessController::class, 'downloadLogs'])->name('download');
        });

        // Supervisor
        Route::prefix('supervisor')->name('supervisor.')->group(function () {
            Route::get('/', [SupervisorController::class, 'index'])->name('index');
            Route::get('/{processName}', [SupervisorController::class, 'show'])->name('show');
            Route::post('/{processName}/start', [SupervisorController::class, 'start'])->name('start');
            Route::post('/{processName}/stop', [SupervisorController::class, 'stop'])->name('stop');
            Route::post('/{processName}/restart', [SupervisorController::class, 'restart'])->name('restart');
            Route::get('/{processName}/logs', [SupervisorController::class, 'getLogs'])->name('logs');
            Route::post('/reload', [SupervisorController::class, 'reload'])->name('reload');
            Route::post('/restart-service', [SupervisorController::class, 'restartSupervisor'])->name('restart-service');
            Route::get('/status/ajax', [SupervisorController::class, 'getStatusAjax'])->name('status-ajax');
            Route::get('/api/scheduled-jobs', [SupervisorController::class, 'getScheduledJobs'])->name('scheduled-jobs');
            Route::post('/api/run-scheduler', [SupervisorController::class, 'runScheduler'])->name('run-scheduler');
            Route::post('/api/run-command', [SupervisorController::class, 'runCommand'])->name('run-command');
            Route::get('/api/list-commands', [SupervisorController::class, 'listCommands'])->name('list-commands');
            Route::get('/backups/list', [SupervisorController::class, 'listBackups'])->name('backups-list');
            Route::post('/backups/create', [SupervisorController::class, 'createBackup'])->name('backup-create');
            Route::post('/backups/{backupId}/restore', [SupervisorController::class, 'restoreBackup'])->name('backup-restore');
            Route::delete('/backups/{backupId}/delete', [SupervisorController::class, 'deleteBackup'])->name('backup-delete');
            Route::get('/backups/{backupId}/download', [SupervisorController::class, 'downloadBackup'])->name('backup-download');
            Route::get('/config-files/list', [SupervisorController::class, 'listConfigFiles'])->name('config-files-list');
            Route::get('/config-files/get', [SupervisorController::class, 'getConfigFile'])->name('config-file-get');
            Route::post('/config-files/update', [SupervisorController::class, 'updateConfigFile'])->name('config-file-update');
        });

        // Uploading Settings
        Route::prefix('uploading')->name('uploading.')->group(function () {
            Route::get('/', [UploadingSettingsController::class, 'index'])->name('index');
            Route::put('/update', [UploadingSettingsController::class, 'update'])->name('update');
        });

        // Maintenance Mode
        Route::prefix('maintenance')->name('maintenance.')->group(function () {
            Route::get('/', [MantenanceSettingsController::class, 'index'])->name('index');
            Route::post('/update', [MantenanceSettingsController::class, 'update'])->name('update');
        });
    });
