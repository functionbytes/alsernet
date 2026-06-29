<?php

use Illuminate\Support\Facades\Route;
use Modules\Ads\Http\Controllers\Admin\AdsController;
use Modules\Ads\Http\Controllers\AdsClickController;

Route::middleware(['web', 'auth'])->group(function () {
    Route::prefix('panel/ads')
        ->name('ads.')
        ->group(function () {
            Route::get('/', [AdsController::class, 'index'])->name('index');
            Route::get('/create', [AdsController::class, 'create'])->name('create');
            Route::post('/', [AdsController::class, 'store'])->name('store');
            Route::get('/{ad}/edit', [AdsController::class, 'edit'])->name('edit');
            Route::put('/{ad}', [AdsController::class, 'update'])->name('update');
            Route::delete('/{ad}', [AdsController::class, 'destroy'])->name('destroy');
        });
});

Route::middleware(['web'])->group(function () {
    Route::get('/ad/click/{key}', [AdsClickController::class, 'click'])->name('ads.click');
});
