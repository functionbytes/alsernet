<?php

use Illuminate\Support\Facades\Route;
use Modules\Backup\Http\Controllers\BackupController;
use Modules\Backup\Http\Controllers\BackupNotificationController;
use Modules\Backup\Http\Controllers\BackupScheduleController;

Route::middleware(['web', 'auth'])
    ->prefix('panel/settings/backups')
    ->group(function () {
        // Backup management
        Route::get('/', [BackupController::class, 'index'])->name('settings.backups.index');
        Route::get('/guide', [BackupController::class, 'guide'])->name('settings.backups.guide');
        Route::get('/setup', [BackupController::class, 'setup'])->name('settings.backups.setup');
        Route::get('/setup/prerequisites', [BackupController::class, 'prerequisites'])->name('settings.backups.prerequisites');
        Route::get('/setup/scheduler-configure-instructions', [BackupController::class, 'schedulerConfigureInstructions'])->name('settings.backups.scheduler.configure-instructions');
        Route::get('/setup/supervisor-status', [BackupController::class, 'supervisorStatus'])->name('settings.backups.supervisor.status');
        Route::get('/setup/supervisor-install-instructions', [BackupController::class, 'supervisorInstallInstructions'])->name('settings.backups.supervisor.install-instructions');
        Route::get('/setup/supervisor-apply-instructions', [BackupController::class, 'supervisorApplyInstructions'])->name('settings.backups.supervisor.apply-instructions');
        Route::get('/setup/supervisor-restart-instructions', [BackupController::class, 'supervisorRestartInstructions'])->name('settings.backups.supervisor.restart-instructions');
        // 301 redirects for backward compatibility with old bookmarks/integrations
        Route::redirect('/setup/scheduler-configure', '/panel/settings/backups/setup/scheduler-configure-instructions', 301);
        Route::redirect('/setup/supervisor-install', '/panel/settings/backups/setup/supervisor-install-instructions', 301);
        Route::redirect('/setup/supervisor-apply', '/panel/settings/backups/setup/supervisor-apply-instructions', 301);
        Route::redirect('/setup/supervisor-restart', '/panel/settings/backups/setup/supervisor-restart-instructions', 301);
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

// Legacy redirects (singular → plural). TODO remove after 2026-12-31
Route::middleware(['web'])->group(function () {
    Route::redirect('panel/setting/backups/{any}', 'panel/settings/backups/{any}', 301)->where('any', '.*');
    Route::redirect('panel/setting/backups', 'panel/settings/backups', 301);
});
