<?php

use Illuminate\Support\Facades\Route;
use Modules\Cookie\Http\Controllers\CookieSettingsController;

Route::get('', [CookieSettingsController::class, 'index'])->name('index');
Route::patch('', [CookieSettingsController::class, 'update'])->name('update');
Route::get('logs', [CookieSettingsController::class, 'logs'])->name('logs');
Route::get('logs/export', [CookieSettingsController::class, 'export'])->name('export');
