<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskChat\Http\Controllers\Api\CannedResponseController;
use Modules\HelpdeskChat\Http\Controllers\Api\CsatSurveyController;
use Modules\HelpdeskChat\Http\Controllers\Api\SessionController;
use Modules\HelpdeskChat\Http\Controllers\Api\WidgetController as ApiWidgetController;
use Modules\HelpdeskChat\Http\Controllers\Webhooks\FacebookController;
use Modules\HelpdeskChat\Http\Controllers\Webhooks\InstagramController;
use Modules\HelpdeskChat\Http\Controllers\Webhooks\WhatsappController;

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
    Route::post('/conversation/{conversation}/messages', [ApiWidgetController::class, 'sendMessage']);
    Route::post('/conversation/{conversation}/upload', [ApiWidgetController::class, 'uploadFile']);
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

    // WhatsApp
    Route::get('/whatsapp/{phone_number}', [WhatsappController::class, 'verify'])->name('webhooks.whatsapp.verify');
    Route::post('/whatsapp/{phone_number}', [WhatsappController::class, 'handle'])->name('webhooks.whatsapp.handle');

    // Evolution API (Baileys)
    Route::post('/evolution/{whatsapp}', [WhatsappController::class, 'handleEvolution'])->name('webhooks.evolution');
});

// Canned Responses API (authenticated - for autocomplete in chat)
// Rate limit: 120 requests per minute per user (higher limit for authenticated autocomplete)
Route::middleware(['auth', 'throttle:120,1'])->prefix('canneds')->group(function () {
    Route::get('/search', [CannedResponseController::class, 'search']);
    Route::get('/{id}', [CannedResponseController::class, 'show']);
});

// Test login endpoint (for debugging - no CSRF required)
Route::post('/test-login', function (\Illuminate\Http\Request $request) {
    $credentials = $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    if (\Illuminate\Support\Facades\Auth::attempt($credentials)) {
        $request->session()->regenerate();

        return response()->json([
            'success' => true,
            'message' => 'Logged in successfully',
            'user' => \Illuminate\Support\Facades\Auth::user(),
        ]);
    }

    return response()->json([
        'success' => false,
        'message' => 'Invalid credentials',
    ], 401);
})->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
