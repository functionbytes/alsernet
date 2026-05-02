<?php

use Illuminate\Support\Facades\Route;
use Modules\Helpdesk\Http\Controllers\Api\HelpcenterWidgetController;
use Modules\HelpdeskLivechat\Http\Controllers\Api\WidgetConversationController;
use Modules\HelpdeskLivechat\Http\Controllers\Pages\WidgetController as WidgetPageController;

/*
|--------------------------------------------------------------------------
| Live Chat widget public API
|--------------------------------------------------------------------------
| Mounted by HelpdeskLivechatServiceProvider with prefix `hd/api` and name
| `helpdesk-livechat.widget.`. Public — no auth required.
| HelpcenterWidgetController stays in Helpdesk because the helpcenter is
| a core helpdesk feature; the widget only consumes it.
| SDK endpoints moved to Engagement module — backwards-compat aliases live
| in EngagementServiceProvider.
*/

Route::get('/settings', [WidgetPageController::class, 'settings'])->name('settings');

Route::get('/helpcenter', [HelpcenterWidgetController::class, 'apiWidget'])->name('helpcenter');
Route::get('/helpcenter/articles/{id}', [HelpcenterWidgetController::class, 'apiArticle'])->name('helpcenter.article');
Route::post('/helpcenter/articles/{id}/feedback', [HelpcenterWidgetController::class, 'apiArticleFeedback'])->name('helpcenter.article.feedback');

Route::post('/conversation', [WidgetConversationController::class, 'store'])->name('conversation.store');
Route::get('/conversation/{id}', [WidgetConversationController::class, 'show'])->name('conversation.show');
Route::get('/conversation/{id}/messages', [WidgetConversationController::class, 'getMessages'])->name('conversation.messages.index');
Route::post('/conversation/{id}/messages', [WidgetConversationController::class, 'sendMessage'])->name('conversation.messages.send');
Route::post('/conversation/{id}/close', [WidgetConversationController::class, 'close'])->name('conversation.close');
