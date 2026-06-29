<?php

use Illuminate\Support\Facades\Route;
use Modules\Newsletter\Http\Controllers\PopupController;
use Modules\Newsletter\Http\Controllers\PublicSubscribeController;
use Modules\Newsletter\Http\Controllers\PublicUnsubscribeController;

Route::post('/subscribe/newsletter', [PublicSubscribeController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('newsletter.subscribe');

Route::post('/unsubscribe/newsletter', [PublicUnsubscribeController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('newsletter.unsubscribe');

Route::get('/ajax/newsletter/popup', [PopupController::class, 'show'])
    ->name('newsletter.popup');
