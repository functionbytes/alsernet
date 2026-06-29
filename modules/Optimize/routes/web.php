<?php

use Illuminate\Support\Facades\Route;
use Modules\Optimize\Http\Controllers\OptimizeController;

Route::get('', [OptimizeController::class, 'index'])->name('index');
Route::get('tools', [OptimizeController::class, 'tools'])->name('tools');
Route::post('', [OptimizeController::class, 'update'])->name('update');
Route::post('reset-stats', [OptimizeController::class, 'resetStats'])->name('reset-stats');
Route::post('run-command', [OptimizeController::class, 'runCommand'])
    ->middleware('throttle:10,1')
    ->name('run-command');
Route::post('run-all', [OptimizeController::class, 'runAll'])
    ->middleware('throttle:3,1')
    ->name('run-all');
