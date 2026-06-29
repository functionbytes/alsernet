<?php

use Illuminate\Support\Facades\Route;
use Modules\Cache\Http\Controllers\Settings\CacheSettingsController;

Route::get('', [CacheSettingsController::class, 'index'])->name('index');
Route::patch('', [CacheSettingsController::class, 'update'])->name('update');
Route::get('monitor', [CacheSettingsController::class, 'monitor'])->name('monitor');
Route::get('stats', [CacheSettingsController::class, 'stats'])->name('stats');
Route::get('redis-stats', [CacheSettingsController::class, 'redisStats'])->name('redis-stats');
Route::post('flush', [CacheSettingsController::class, 'flush'])->name('flush');
