<?php

use Illuminate\Support\Facades\Route;
use Modules\Chat\Http\Controllers\Api\CannedResponseController;
use Modules\Chat\Http\Controllers\Api\CsatSurveyController;
use Modules\Chat\Http\Controllers\Api\SessionController;
use Modules\Chat\Http\Controllers\Api\Webhooks\FacebookController;
use Modules\Chat\Http\Controllers\Api\Webhooks\InstagramController;
use Modules\Chat\Http\Controllers\Api\Webhooks\WhatsappController;
use Modules\Chat\Http\Controllers\Api\WidgetController as ApiWidgetController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Widget API (no authentication - verified by website_token)
// Rate limit: 60 requests per minute per IP
Route::prefix('widget')->middleware('throttle:60,1')->group(function () {
    // Session management
    Route::post('/session/init', [SessionController::class, 'init']);
    Route::post('/session/sync', [SessionController::class, 'sync']);
    Route::get('/session', [SessionController::class, 'show']);

    // Legacy widget routes
    Route::get('/config', [ApiWidgetController::class, 'config']);
    Route::get('/translations/{lang?}', [ApiWidgetController::class, 'getTranslations']);
    Route::post('/contact', [ApiWidgetController::class, 'createContact']);
    Route::get('/conversation/{conversation}/messages', [ApiWidgetController::class, 'getMessages']);
    Route::post('/conversation/{conversation}/messages', [ApiWidgetController::class, 'sendMessage'])
        ->middleware('widget.message.ratelimit');
    Route::post('/conversation/{conversation}/upload', [ApiWidgetController::class, 'uploadFile'])
        ->middleware('widget.message.ratelimit');
    Route::post('/conversation/{conversation}/read', [ApiWidgetController::class, 'markAsRead']);
});

// CSAT Survey API (public - token-based)
// Rate limit: 30 requests per minute per IP (lower limit for surveys)
Route::prefix('csat')->middleware('throttle:30,1')->group(function () {
    Route::get('/survey/{token}', [CsatSurveyController::class, 'show']);
    Route::post('/survey/{token}/submit', [CsatSurveyController::class, 'submit']);
});

// Webhooks (no authentication required - verified by signature/token)
// Rate limit: 300 requests per minute per IP (higher limit for webhooks from platforms)
Route::prefix('webhooks')->middleware('throttle:300,1')->group(function () {
    // Facebook Messenger
    Route::get('/facebook', [FacebookController::class, 'verify']);
    Route::post('/facebook', [FacebookController::class, 'handle']);

    // Instagram
    Route::get('/instagram', [InstagramController::class, 'verify'])->name('webhooks.instagram.verify');
    Route::post('/instagram', [InstagramController::class, 'handle'])->name('webhooks.instagram.handle');

    // WhatsApp (single endpoint - handles all phone numbers via payload)
    Route::get('/whatsapp', [WhatsappController::class, 'verify'])->name('api.webhooks.whatsapp.verify');
    Route::post('/whatsapp', [WhatsappController::class, 'handle'])->name('api.webhooks.whatsapp.handle');

    // Evolution API (Baileys)
    Route::post('/evolution/{whatsapp}', [WhatsappController::class, 'handleEvolution'])->name('webhooks.evolution');
});

// Canned Responses API (authenticated - for autocomplete in chat)
// Rate limit: 120 requests per minute per user (higher limit for authenticated autocomplete)
Route::middleware(['auth', 'throttle:120,1'])->prefix('canneds')->group(function () {
    Route::get('/search', [CannedResponseController::class, 'search']);
    Route::get('/{id}', [CannedResponseController::class, 'show']);
});
