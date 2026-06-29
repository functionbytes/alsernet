<?php

use Illuminate\Support\Facades\Route;
use Modules\Cookie\Http\Controllers\CookieInventoryController;
use Modules\Cookie\Http\Controllers\CookieSettingsController;

Route::get('', [CookieSettingsController::class, 'index'])->name('index');
Route::patch('', [CookieSettingsController::class, 'update'])->name('update');
Route::get('logs', [CookieSettingsController::class, 'logs'])->name('logs');
Route::get('logs/export', [CookieSettingsController::class, 'export'])->name('export');

Route::get('inventory', [CookieInventoryController::class, 'index'])->name('inventory');
Route::post('inventory', [CookieInventoryController::class, 'store'])->name('inventory.store');
Route::put('inventory/{cookieInventory}', [CookieInventoryController::class, 'update'])->name('inventory.update');
Route::delete('inventory/{cookieInventory}', [CookieInventoryController::class, 'destroy'])->name('inventory.destroy');
