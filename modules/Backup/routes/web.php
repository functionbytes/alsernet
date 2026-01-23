<?php

use Illuminate\Support\Facades\Route;
use Modules\Backup\Http\Controllers\BackupController;
use Modules\Backup\Http\Controllers\BackupScheduleController;

Route::middleware(['web', 'auth'])
    ->prefix('settings/backups')
    ->group(function () {
        // Backup management
        Route::get('/', [BackupController::class, 'index'])->name('settings.backups.index');
        Route::get('/create', [BackupController::class, 'create'])->name('settings.backups.create');
        Route::post('/', [BackupController::class, 'store'])->name('settings.backups.store');
        Route::get('/{filename}/download', [BackupController::class, 'download'])->name('settings.backups.download');
        Route::delete('/{filename}', [BackupController::class, 'destroy'])->name('settings.backups.destroy');
        Route::get('/status', [BackupController::class, 'getStatus'])->name('settings.backups.status');

        // Backup schedule management
        Route::prefix('schedules')->group(function () {
            Route::get('/', [BackupScheduleController::class, 'index'])->name('settings.backup.schedules.index');
            Route::get('/create', [BackupScheduleController::class, 'create'])->name('settings.backup.schedules.create');
            Route::post('/', [BackupScheduleController::class, 'store'])->name('settings.backup.schedules.store');
            Route::get('/{id}/edit', [BackupScheduleController::class, 'edit'])->name('settings.backup.schedules.edit');
            Route::put('/{id}', [BackupScheduleController::class, 'update'])->name('settings.backup.schedules.update');
            Route::delete('/{id}', [BackupScheduleController::class, 'destroy'])->name('settings.backup.schedules.destroy');
            Route::post('/{id}/toggle', [BackupScheduleController::class, 'toggle'])->name('settings.backup.schedules.toggle');
            Route::get('/{id}/details', [BackupScheduleController::class, 'getScheduleDetails'])->name('settings.backup.schedules.details');
        });

    });
