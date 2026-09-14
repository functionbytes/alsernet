<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskPrestashop\Http\Controllers\Api\PsEventReceiverController;
use Modules\HelpdeskPrestashop\Http\Middleware\VerifyAlsernetHmac;

Route::post('/event', [PsEventReceiverController::class, 'handle'])
    ->middleware(VerifyAlsernetHmac::class)
    ->name('event');
