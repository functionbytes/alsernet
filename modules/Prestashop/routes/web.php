<?php

use Illuminate\Support\Facades\Route;
use Modules\Prestashop\Http\Controllers\PrestashopSettingsController;

/*
|--------------------------------------------------------------------------
| Prestashop Module Routes
|--------------------------------------------------------------------------
|
| Prefix: /settings/prestashop (aplicado por RouteServiceProvider)
| Name: prestashop.* (aplicado por RouteServiceProvider)
|
*/

// Dashboard
Route::get('/', [PrestashopSettingsController::class, 'index'])->name('index');
Route::get('/edit', [PrestashopSettingsController::class, 'edit'])->name('edit');
Route::put('/update', [PrestashopSettingsController::class, 'update'])->name('update');

// Connection & Status
Route::post('/check-connection', [PrestashopSettingsController::class, 'checkConnection'])->name('check-connection');
Route::post('/toggle-active', [PrestashopSettingsController::class, 'toggleActive'])->name('toggle-active');
Route::post('/test-sync', [PrestashopSettingsController::class, 'testSync'])->name('test-sync');

// Statistics
Route::get('/stats', [PrestashopSettingsController::class, 'getStats'])->name('stats');
Route::post('/reset-stats', [PrestashopSettingsController::class, 'resetStats'])->name('reset-stats');

// Product Blockades
Route::prefix('blockades')->name('blockades.')->group(function () {
    Route::post('/sync', [PrestashopSettingsController::class, 'syncProductBlockades'])->name('sync');
    Route::get('/status', [PrestashopSettingsController::class, 'getBlockadesSyncStatus'])->name('status');
    Route::post('/create', [PrestashopSettingsController::class, 'createBlockade'])->name('create');
    Route::delete('/delete', [PrestashopSettingsController::class, 'deleteBlockade'])->name('delete');
    Route::post('/labels', [PrestashopSettingsController::class, 'saveBlockadeLabels'])->name('labels');
});
