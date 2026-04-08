<?php

use Illuminate\Support\Facades\Route;
use Modules\Newsletter\Http\Controllers\PublicSubscribeController;

Route::post('/subscribe/newsletter', [PublicSubscribeController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('newsletter.subscribe');
