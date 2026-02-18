<?php

use Illuminate\Support\Facades\Route;
use Modules\Optimize\Http\Controllers\OptimizeController;

Route::get('', [OptimizeController::class, 'index'])->name('index');
Route::post('', [OptimizeController::class, 'update'])->name('update');
