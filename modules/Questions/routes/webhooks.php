<?php

use Illuminate\Support\Facades\Route;
use Modules\Questions\Http\Controllers\Api\QuestionEventReceiverController;
use Modules\Questions\Http\Middleware\VerifyQuestionsHmac;

Route::post('/event', [QuestionEventReceiverController::class, 'handle'])
    ->middleware(VerifyQuestionsHmac::class)
    ->name('event');
