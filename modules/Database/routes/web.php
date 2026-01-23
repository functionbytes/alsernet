<?php

use Illuminate\Support\Facades\Route;
use Modules\Database\Http\Controllers\DatabaseCleanupController;
use Modules\Database\Http\Controllers\DatabaseSettingsController;

/*
|--------------------------------------------------------------------------
| Database Module Routes
|--------------------------------------------------------------------------
|
| Rutas para el módulo de configuración y limpieza de base de datos
| Prefix: /settings/database (aplicado por ServiceProvider)
| Name: backups.database.* (aplicado por ServiceProvider)
|
| Permisos: Usa Spatie Permission middleware
*/

// Database Settings Routes
Route::middleware('can:database.backups.view')->group(function () {
    Route::get('/', [DatabaseSettingsController::class, 'index'])->name('index');
});

Route::middleware('can:database.backups.update')->group(function () {
    Route::get('/edit', [DatabaseSettingsController::class, 'edit'])->name('edit');
    Route::put('/update', [DatabaseSettingsController::class, 'update'])->name('update');
});

Route::middleware('can:database.backups.test_connection')->group(function () {
    Route::post('/check-connection', [DatabaseSettingsController::class, 'checkConnection'])->name('check-connection');
});

// Database Cleanup Routes
Route::prefix('cleanup')->name('cleanup.')->group(function () {
    Route::middleware('can:database.cleanup.view')->group(function () {
        Route::get('/', [DatabaseCleanupController::class, 'index'])->name('index');
    });

    Route::middleware('can:database.cleanup.truncate')->group(function () {
        Route::post('/truncate', [DatabaseCleanupController::class, 'truncate'])->name('truncate');
    });

    Route::middleware('can:database.cleanup.get_table_count')->group(function () {
        Route::post('/table-count', [DatabaseCleanupController::class, 'getTableCount'])->name('table-count');
    });
});
