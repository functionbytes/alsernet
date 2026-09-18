<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskHelpcenter\Http\Controllers\Api\HelpcenterWidgetController;
use Modules\HelpdeskLivechat\Http\Controllers\Api\LivestreamController;
use Modules\HelpdeskLivechat\Http\Controllers\Api\WebRtcSignalingController;
use Modules\HelpdeskLivechat\Http\Controllers\Api\WidgetConversationController;
use Modules\HelpdeskLivechat\Http\Controllers\Api\WidgetSessionController;
use Modules\HelpdeskLivechat\Http\Controllers\Pages\WidgetController as WidgetPageController;
use Modules\HelpdeskLivechat\Http\Middleware\ThrottleByWebsiteToken;
use Modules\HelpdeskTickets\Http\Controllers\Api\WidgetTicketsController;

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
|
| Rate limits are applied per-endpoint. The global throttle:120,1 has been
| removed from the ServiceProvider group so limits are not multiplied.
*/

Route::get('/settings', [WidgetPageController::class, 'settings'])->name('settings');

// Helpcenter widget — sin throttle propio antes (unico grupo del archivo sin
// limite), a diferencia de todos sus vecinos. search() corre FULLTEXT+LIKE
// por request; feedback() incrementa contadores sin dedup (a diferencia de
// ArticleVoteController::vote, que sí tiene dedup por cookie+IP).
Route::middleware('throttle:60,1')->group(function () {
    Route::get('/helpcenter', [HelpcenterWidgetController::class, 'apiWidget'])->name('helpcenter');
    Route::get('/helpcenter/articles/{id}', [HelpcenterWidgetController::class, 'apiArticle'])->name('helpcenter.article');
});

Route::middleware('throttle:30,1')->group(function () {
    Route::get('/helpcenter/search', [HelpcenterWidgetController::class, 'apiSearch'])->name('helpcenter.search');
});

Route::middleware('throttle:10,1')->group(function () {
    Route::post('/helpcenter/articles/{id}/feedback', [HelpcenterWidgetController::class, 'apiArticleFeedback'])->name('helpcenter.article.feedback');
});

// Create conversation — most restrictive: 10 per minute per IP, plus a
// per-website_token quota (distributed abuse against a single store cannot
// be stopped by IP throttling alone; Origin/Referer are spoofable).
Route::middleware(['throttle:10,1', ThrottleByWebsiteToken::class.':conversations'])->group(function () {
    Route::post('/conversation', [WidgetConversationController::class, 'store'])->name('conversation.store');
});

// Public ticket creation from widget — 5 per minute per IP + per-token quota
Route::middleware(['throttle:5,1', ThrottleByWebsiteToken::class.':tickets'])->group(function () {
    Route::post('/tickets', [WidgetTicketsController::class, 'store'])->name('tickets.store');
});

// Customer ticket list — 30 per minute
Route::middleware('throttle:30,1')->group(function () {
    Route::get('/tickets', [WidgetTicketsController::class, 'index'])->name('tickets.index');
});

// Ticket categories list — 60 per minute
Route::middleware('throttle:60,1')->group(function () {
    Route::get('/tickets/categories', [WidgetTicketsController::class, 'categories'])->name('tickets.categories');
});

// Send message + typing — 30 per minute per IP + per-token quota on messages
Route::middleware(['throttle:30,1', ThrottleByWebsiteToken::class.':messages'])->group(function () {
    Route::post('/conversation/{id}/messages', [WidgetConversationController::class, 'sendMessage'])->name('conversation.messages.send');
    Route::post('/conversation/{id}/typing', [WidgetConversationController::class, 'typing'])->name('conversation.typing');
});

// Read conversation data + read receipts — 60 per minute
Route::middleware('throttle:60,1')->group(function () {
    Route::get('/conversation/{id}', [WidgetConversationController::class, 'show'])->name('conversation.show');
    Route::get('/conversation/{id}/messages', [WidgetConversationController::class, 'getMessages'])->name('conversation.messages.index');
    Route::get('/conversation/{id}/queue-position', [WidgetConversationController::class, 'queuePosition'])->name('conversation.queue-position');
    Route::post('/conversation/{id}/read', [WidgetConversationController::class, 'markAsRead'])->name('conversation.read');
    Route::post('/conversation/{id}/close', [WidgetConversationController::class, 'close'])->name('conversation.close');
});

// Email transcript — 5 per minute (anti-spam)
Route::middleware('throttle:5,1')->group(function () {
    Route::post('/conversation/{id}/email-transcript', [WidgetConversationController::class, 'emailTranscript'])->name('conversation.email-transcript');
});

// Heartbeat — 120 per minute (frequent polling)
Route::middleware('throttle:120,1')->group(function () {
    Route::post('/session/heartbeat', [WidgetSessionController::class, 'heartbeat'])->name('session.heartbeat');
});

// Live view (rrweb) — 200 per minute (real-time flush at ~500ms with bursts).
// Even a noisy page rarely flushes more than 120/min in steady state; the
// extra headroom absorbs activity bursts (clicks, scroll storms).
Route::middleware('throttle:200,1')->group(function () {
    Route::post('/livestream/{conversation}/events', [LivestreamController::class, 'ingest'])
        ->name('livestream.ingest');
});

// WebRTC signaling — 60 per minute (offer/answer/ICE exchange)
Route::middleware('throttle:60,1')->group(function () {
    Route::post('/webrtc/{conversation}/offer', [WebRtcSignalingController::class, 'offer'])
        ->name('webrtc.offer');
    Route::post('/webrtc/{conversation}/ice', [WebRtcSignalingController::class, 'ice'])
        ->name('webrtc.ice');
    Route::post('/webrtc/{conversation}/end', [WebRtcSignalingController::class, 'end'])
        ->name('webrtc.end');
});
