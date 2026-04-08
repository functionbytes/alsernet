<?php

use Illuminate\Support\Facades\Route;
use Modules\Backup\Http\Controllers\BackupController;
use Modules\Backup\Http\Controllers\BackupNotificationController;
use Modules\Backup\Http\Controllers\BackupScheduleController;

Route::middleware(['web', 'auth'])
    ->prefix('panel/setting/backups')
    ->group(function () {
        // Backup management
        Route::get('/', [BackupController::class, 'index'])->name('settings.backups.index');
        Route::get('/guide', [BackupController::class, 'guide'])->name('settings.backups.guide');
        Route::get('/setup', [BackupController::class, 'setup'])->name('settings.backups.setup');
        Route::get('/setup/prerequisites', [BackupController::class, 'prerequisites'])->name('settings.backups.prerequisites');
        Route::post('/setup/scheduler-configure', [BackupController::class, 'schedulerConfigure'])->name('settings.backups.scheduler.configure');
        Route::get('/setup/supervisor-status', [BackupController::class, 'supervisorStatus'])->name('settings.backups.supervisor.status');
        Route::post('/setup/supervisor-install', [BackupController::class, 'supervisorInstall'])->name('settings.backups.supervisor.install');
        Route::post('/setup/supervisor-apply', [BackupController::class, 'supervisorApply'])->name('settings.backups.supervisor.apply');
        Route::post('/setup/supervisor-restart', [BackupController::class, 'supervisorRestart'])->name('settings.backups.supervisor.restart');
        Route::get('/create', [BackupController::class, 'create'])->name('settings.backups.create');
        Route::post('/', [BackupController::class, 'store'])->name('settings.backups.store');
        Route::get('/{filename}/download', [BackupController::class, 'download'])->where('filename', '[^/]+')->name('settings.backups.download');
        Route::delete('/{filename}', [BackupController::class, 'destroy'])->where('filename', '[^/]+')->name('settings.backups.destroy');
        Route::get('/status', [BackupController::class, 'getStatus'])->name('settings.backups.status');

        // Backup notification settings
        Route::get('/notifications', [BackupNotificationController::class, 'index'])->name('settings.backups.notifications');
        Route::post('/notifications', [BackupNotificationController::class, 'update'])->name('settings.backups.notifications.update');

        // Backup schedule management
        Route::prefix('schedules')->group(function () {
            Route::get('/', [BackupScheduleController::class, 'index'])->name('settings.backup.schedules.index');
            Route::post('/bulk-action', [BackupScheduleController::class, 'bulkAction'])->name('settings.backup.schedules.bulk-action');
            Route::get('/create', [BackupScheduleController::class, 'create'])->name('settings.backup.schedules.create');
            Route::post('/', [BackupScheduleController::class, 'store'])->name('settings.backup.schedules.store');
            Route::get('/{schedule}/edit', [BackupScheduleController::class, 'edit'])->name('settings.backup.schedules.edit');
            Route::put('/{schedule}', [BackupScheduleController::class, 'update'])->name('settings.backup.schedules.update');
            Route::delete('/{schedule}', [BackupScheduleController::class, 'destroy'])->name('settings.backup.schedules.destroy');
            Route::post('/{schedule}/toggle', [BackupScheduleController::class, 'toggle'])->name('settings.backup.schedules.toggle');
            Route::get('/{schedule}/details', [BackupScheduleController::class, 'getScheduleDetails'])->name('settings.backup.schedules.details');
        });

    });
