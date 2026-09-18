<?php

use Illuminate\Support\Facades\Route;
use Modules\Forms\Http\Controllers\Api\FormSubmissionReceiverController;
use Modules\Forms\Http\Middleware\VerifyAlsernetFormsHmac;

Route::post('/submission', [FormSubmissionReceiverController::class, 'handle'])
    ->middleware(VerifyAlsernetFormsHmac::class)
    ->name('submission');
