<?php

/**
 * Chat Module - Customer Routes
 * Prefix: /customer/helpdesk
 * Middleware: auth, role:customer
 */

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskChat\Http\Controllers\Customer\ConversationController;
use Modules\HelpdeskChat\Http\Controllers\Customer\MessageController;

// My Conversations
Route::prefix('conversation')->name('conversation.')->group(function () {
    Route::get('/', [ConversationController::class, 'index'])->name('index');
    Route::get('/create', [ConversationController::class, 'create'])->name('create');
    Route::post('/', [ConversationController::class, 'store'])->name('store');
    Route::get('/{conversation}', [ConversationController::class, 'show'])->name('show');
});

// My Messages
Route::post('conversation/{conversation}/messages', [MessageController::class, 'store'])
    ->name('conversation.messages.store');
