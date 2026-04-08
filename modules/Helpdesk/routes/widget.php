<?php

use Illuminate\Support\Facades\Route;
use Modules\Helpdesk\Http\Controllers\Api\Helpdesk\WidgetConversationController;

// Widget/Live chat routes (public — no auth required)
Route::post('conversations', [WidgetConversationController::class, 'store'])->name('conversations.store');
Route::get('conversations/{id}', [WidgetConversationController::class, 'show'])->name('conversations.show');
Route::post('conversations/{id}/messages', [WidgetConversationController::class, 'sendMessage'])->name('conversations.messages.store');
Route::get('conversations/{id}/messages', [WidgetConversationController::class, 'getMessages'])->name('conversations.messages.index');
Route::post('conversations/{id}/close', [WidgetConversationController::class, 'close'])->name('conversations.close');

// Agent reply requires auth (handled internally by the controller)
Route::middleware('auth:sanctum')->post('conversations/{id}/reply', [WidgetConversationController::class, 'replyAsAgent'])->name('conversations.reply');
