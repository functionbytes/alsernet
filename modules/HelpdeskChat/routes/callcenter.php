<?php

/**
 * Chat Module - Callcenter Routes
 * Prefix: /callcenter/helpdesk
 * Middleware: auth, role:admin|callcenter
 */

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskChat\Http\Controllers\Callcenter\ConversationController;
use Modules\HelpdeskChat\Http\Controllers\Callcenter\MessageController;

// My Conversations
Route::prefix('conversation')->name('conversation.')->group(function () {
    Route::get('/', [ConversationController::class, 'index'])->name('index');
    Route::get('/{conversation}', [ConversationController::class, 'show'])->name('show');

    // Actions - Solo conversaciones asignadas
    Route::post('/{conversation}/close', [ConversationController::class, 'close'])->name('close');
    Route::post('/{conversation}/take', [ConversationController::class, 'take'])->name('take');
});

// Messages
Route::post('conversation/{conversation}/messages', [MessageController::class, 'store'])
    ->name('conversation.messages.store');
