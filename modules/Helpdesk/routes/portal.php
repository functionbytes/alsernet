<?php

use Illuminate\Support\Facades\Route;
use Modules\Helpdesk\Http\Controllers\Portal\PortalAuthController;
use Modules\Helpdesk\Http\Controllers\Portal\PortalDashboardController;
use Modules\Helpdesk\Http\Middleware\PortalAuth;

/*
|--------------------------------------------------------------------------
| Customer Portal routes
|--------------------------------------------------------------------------
| Public portal for customers to view their conversation history.
| Uses session-based auth via PortalAuth middleware (not Laravel auth).
*/

Route::prefix('portal')
    ->name('helpdesk.portal.')
    ->middleware(['web', 'throttle:helpdesk-customer-portal'])
    ->group(function () {

        // Auth — no PortalAuth middleware
        Route::get('login', [PortalAuthController::class, 'loginForm'])->name('login');
        Route::post('login/send-link', [PortalAuthController::class, 'sendLink'])->name('send-link')->middleware('throttle:10,5');
        Route::get('verify/{token}', [PortalAuthController::class, 'verify'])->name('verify');
        Route::post('logout', [PortalAuthController::class, 'logout'])->name('logout');

        // Protected — requires PortalAuth
        Route::middleware(PortalAuth::class)->group(function () {
            Route::get('/', [PortalDashboardController::class, 'dashboard'])->name('dashboard');
            Route::get('conversations/{id}', [PortalDashboardController::class, 'showConversation'])->name('conversation');
            Route::post('conversations/{id}/messages', [PortalDashboardController::class, 'sendMessage'])->name('conversation.message');
            Route::get('profile', [PortalDashboardController::class, 'profile'])->name('profile');
            Route::post('profile', [PortalDashboardController::class, 'updateProfile'])->name('profile.update');
        });
    });
