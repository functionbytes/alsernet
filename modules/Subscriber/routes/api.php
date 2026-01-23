<?php

use Illuminate\Support\Facades\Route;
use Modules\Subscriber\Http\Controllers\Api\SubscribersController;

Route::post('/process', [SubscribersController::class, 'process'])->name('process');
Route::post('/campaigns', [SubscribersController::class, 'campaigns'])->name('campaigns');
