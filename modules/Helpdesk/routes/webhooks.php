<?php

use Illuminate\Support\Facades\Route;
use Modules\Helpdesk\Http\Controllers\WebhookController;

// These routes are public (no auth) — Meta webhook verification and delivery require open access.
// CSRF is already excluded via VerifyCsrfToken::$except = ['webhooks/*'].

Route::prefix('webhooks/helpdesk')->name('helpdesk.webhooks.')->group(function () {
    // WhatsApp Business API
    Route::get('whatsapp', [WebhookController::class, 'whatsappVerify'])->name('whatsapp.verify');
    Route::post('whatsapp', [WebhookController::class, 'whatsappIncoming'])->name('whatsapp.incoming');

    // Facebook Messenger
    Route::get('facebook', [WebhookController::class, 'facebookVerify'])->name('facebook.verify');
    Route::post('facebook', [WebhookController::class, 'facebookIncoming'])->name('facebook.incoming');

    // Instagram DMs
    Route::get('instagram', [WebhookController::class, 'instagramVerify'])->name('instagram.verify');
    Route::post('instagram', [WebhookController::class, 'instagramIncoming'])->name('instagram.incoming');
});
