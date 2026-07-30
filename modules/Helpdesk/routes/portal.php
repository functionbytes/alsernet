<?php

use Illuminate\Support\Facades\Route;
use Modules\Helpdesk\Http\Controllers\Portal\PortalDashboardController;
use Modules\Helpdesk\Http\Middleware\PortalAuth;

/*
|--------------------------------------------------------------------------
| Customer Portal — Conversations
|--------------------------------------------------------------------------
| Login/logout at /portal are handled by the HelpdeskTickets module.
| Both share the portal_customer_id session key.
| These routes add conversation-viewing to the existing portal.
*/

Route::prefix('portal')
    ->name('helpdesk.portal.')
    ->middleware(['web', 'throttle:helpdesk-customer-portal'])
    ->group(function () {

        // Protected — requires portal session
        Route::middleware(PortalAuth::class)->group(function () {
            Route::get('conversations', [PortalDashboardController::class, 'dashboard'])->name('conversations');
            Route::get('conversations/{id}', [PortalDashboardController::class, 'showConversation'])->name('conversation');
            Route::post('conversations/{id}/messages', [PortalDashboardController::class, 'sendMessage'])->name('conversation.message');
            Route::get('profile', [PortalDashboardController::class, 'profile'])->name('profile');
            Route::post('profile', [PortalDashboardController::class, 'updateProfile'])->name('profile.update');
        });
    });
