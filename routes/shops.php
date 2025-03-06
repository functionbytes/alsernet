<?php

use App\Http\Controllers\Shops\Subscribers\SubscribersController;
use App\Http\Controllers\Shops\Settings\SettingsController;
use App\Http\Controllers\Shops\DashboardController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'shop', 'middleware' => ['auth', 'roles:shops']], function () {

    Route::get('/', [DashboardController::class, 'dashboard'])->name('shop.dashboard');

    Route::group(['prefix' => 'settings'], function () {
        Route::get('/', [SettingsController::class, 'index'])->name('shop.settings');
        Route::post('/update', [SettingsController::class, 'update'])->name('shop.settings.update');
    });

    Route::group(['prefix' => 'subscribers'], function () {
        Route::get('/', [SubscribersController::class, 'index'])->name('shop.subscribers');
        Route::get('/create', [SubscribersController::class, 'create'])->name('shop.subscribers.create');
        Route::get('/lists', [SubscribersController::class, 'lists'])->name('shop.subscribers.lists');
        Route::post('/update', [SubscribersController::class, 'update'])->name('shop.subscribers.update');
        Route::get('/edit/{uid}', [SubscribersController::class, 'edit'])->name('shop.subscribers.edit');
        Route::get('/view/{uid}', [SubscribersController::class, 'view'])->name('shop.subscribers.view');
        Route::get('/destroy/{uid}', [SubscribersController::class, 'destroy'])->name('shop.subscribers.destroy');
        Route::get('/list/{uid}', [SubscribersController::class, 'list'])->name('shop.subscribers.list');
        Route::get('/logs/{slack}', [SubscribersController::class, 'logs'])->name('shop.subscribers.logs');
    });

});
