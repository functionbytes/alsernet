<?php

use Illuminate\Support\Facades\Route;
use Modules\Mailer\Http\Controllers\MailerEndpointController;

Route::post('{slug}/send', [MailerEndpointController::class, 'send'])->name('send');
Route::get('{slug}/info', [MailerEndpointController::class, 'info'])->name('info');
Route::get('{slug}/status', [MailerEndpointController::class, 'status'])->name('status');
