<?php

use Illuminate\Support\Facades\Route;
use Modules\Helpdesk\Http\Controllers\Api\HelpcenterWidgetController;
use Modules\Helpdesk\Http\Controllers\Api\WidgetConversationController;
use Modules\Helpdesk\Http\Controllers\Pages\WidgetController as WidgetPageController;

/*
|--------------------------------------------------------------------------
| Helpdesk widget public API
|--------------------------------------------------------------------------
| Mounted by RouteServiceProvider with prefix `hd/api` and name
| `helpdesk.widget.`. Public — no auth required.
*/

Route::get('/settings', [WidgetPageController::class, 'settings'])->name('settings');

// Helpcenter (categories + articles + feedback)
Route::get('/helpcenter', [HelpcenterWidgetController::class, 'apiWidget'])->name('helpcenter');
Route::get('/helpcenter/articles/{id}', [HelpcenterWidgetController::class, 'apiArticle'])->name('helpcenter.article');
Route::post('/helpcenter/articles/{id}/feedback', [HelpcenterWidgetController::class, 'apiArticleFeedback'])->name('helpcenter.article.feedback');

// Visitor conversation lifecycle
Route::post('/conversation', [WidgetConversationController::class, 'store'])->name('conversation.store');
Route::get('/conversation/{id}', [WidgetConversationController::class, 'show'])->name('conversation.show');
Route::get('/conversation/{id}/messages', [WidgetConversationController::class, 'getMessages'])->name('conversation.messages.index');
Route::post('/conversation/{id}/messages', [WidgetConversationController::class, 'sendMessage'])->name('conversation.messages.send');
Route::post('/conversation/{id}/close', [WidgetConversationController::class, 'close'])->name('conversation.close');
