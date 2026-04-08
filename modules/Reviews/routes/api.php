<?php

use Illuminate\Support\Facades\Route;
use Modules\Reviews\Http\Controllers\Api\ReviewController;
use Modules\Reviews\Http\Controllers\ReviewBadgeController;
use Modules\Reviews\Http\Controllers\ReviewWebhookController;
/**
 * Reviews API Routes (v1)
 *
 * Rate Limits:
 * - Admin (super-admin|administrative|manager): 1000 requests/hour
 * - Authenticated users: 100 requests/hour
 * - Guest users (by IP): 20 requests/hour
 *
 * All responses include:
 * - X-API-Version: 1.0
 * - X-Request-ID: UUID for tracking
 * - X-RateLimit-Limit: Max requests allowed
 * - X-RateLimit-Remaining: Requests remaining
 * - X-RateLimit-Reset: Unix timestamp when limit resets
 *
 * Rate limit exceeded (429) responses include:
 * - Retry-After: Seconds until retry allowed
 * - Error message with retry time
 */
use Modules\Reviews\Http\Controllers\ReviewWidgetController;
use Modules\Reviews\Http\Middleware\AddApiHeaders;
use Modules\Reviews\Http\Middleware\HandleApiExceptions;
use Modules\Reviews\Http\Middleware\VerifyGoogleWebhookSignature;

// Webhook routes — no auth, no CSRF (external requests from Google Cloud Pub/Sub)
Route::prefix('reviews')
    ->middleware(['api', 'throttle:reviews:webhooks'])
    ->group(function () {
        Route::post('webhooks/google', [ReviewWebhookController::class, 'handleGoogleNotification'])
            ->middleware(VerifyGoogleWebhookSignature::class)
            ->name('reviews.webhooks.google');

        Route::get('webhooks/google/verify', [ReviewWebhookController::class, 'verifyGoogleWebhook'])
            ->name('reviews.webhooks.google.verify');
    });

Route::middleware(['api', 'auth:sanctum', 'throttle:reviews:api', AddApiHeaders::class, HandleApiExceptions::class])
    ->prefix('reviews')
    ->name('api.reviews.')
    ->group(function () {
        // GET /api/reviews - List all reviews with filters and pagination
        Route::get('/', [ReviewController::class, 'index'])->name('index');

        // GET /api/reviews/stats - Get review statistics
        Route::get('stats', [ReviewController::class, 'stats'])->name('stats');

        // GET /api/reviews/{review} - Get single review details
        Route::get('{review}', [ReviewController::class, 'show'])->name('show');

        // GET /api/reviews/{review}/suggestions - Get AI reply suggestions
        Route::get('{review}/suggestions', [ReviewController::class, 'suggestions'])->name('suggestions');
    });

// Public widget endpoints — no auth, rate-limited by IP
Route::prefix('reviews-widget')
    ->middleware(['api', 'throttle:60,1'])
    ->group(function () {
        Route::get('{token}.js', [ReviewWidgetController::class, 'widget'])->name('reviews.widget.js');
        Route::get('{token}/reviews', [ReviewWidgetController::class, 'reviews'])->name('reviews.widget.reviews');
    });

// Public badge endpoint — no auth, embeddable on external sites
Route::middleware(['api', 'throttle:120,1'])
    ->group(function () {
        Route::get('badge/{location}/preview.svg', [ReviewBadgeController::class, 'publicBadge'])->name('reviews.badge.public');
    });
