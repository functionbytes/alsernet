<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskLivechat\Http\Controllers\Api\PreChatFormApiController;

Route::get('pre-chat-form', [PreChatFormApiController::class, 'show'])->name('pre-chat-form.show');
Route::post('pre-chat-form', [PreChatFormApiController::class, 'submit'])->name('pre-chat-form.submit');
