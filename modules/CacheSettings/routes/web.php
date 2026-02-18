<?php

use Illuminate\Support\Facades\Route;
use Modules\CacheSettings\Http\Controllers\Settings\CacheSettingsController;

Route::get('', [CacheSettingsController::class, 'index'])->name('index');
Route::patch('', [CacheSettingsController::class, 'update'])->name('update');
