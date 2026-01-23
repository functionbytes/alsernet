<?php

use Illuminate\Support\Facades\Route;
use Modules\Subscriber\Http\Controllers\Shops\SubscribersController;

Route::get('/', [SubscribersController::class, 'index'])->name('index');
Route::get('/create', [SubscribersController::class, 'create'])->name('create');
Route::get('/edit/{uid}', [SubscribersController::class, 'edit'])->name('edit');
Route::get('/logs/{uid}', [SubscribersController::class, 'logs'])->name('logs');
