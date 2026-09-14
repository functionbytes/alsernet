<?php

use Illuminate\Support\Facades\Route;
use Modules\Reviews\Http\Controllers\Api\ReviewEventReceiverController;
use Modules\Reviews\Http\Middleware\VerifyReviewsHmac;

Route::post('/event', [ReviewEventReceiverController::class, 'handle'])
    ->middleware(VerifyReviewsHmac::class)
    ->name('event');
