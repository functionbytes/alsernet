<?php

use Illuminate\Support\Facades\Route;
use Modules\Mailer\Http\Controllers\MailerEndpointController;

Route::post('{slug}/send', [MailerEndpointController::class, 'send'])
    ->middleware('throttle:60,1')
    ->name('send');

Route::get('{slug}/info', [MailerEndpointController::class, 'info'])
    ->middleware('throttle:30,1')
    ->name('info');

Route::get('{slug}/status', [MailerEndpointController::class, 'status'])
    ->middleware('throttle:30,1')
    ->name('status');
