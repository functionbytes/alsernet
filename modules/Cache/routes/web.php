<?php

use Illuminate\Support\Facades\Route;
use Modules\Cache\Http\Controllers\Settings\CacheController;

Route::get('', [CacheController::class, 'index'])->name('index');
Route::patch('', [CacheController::class, 'update'])->name('update');
