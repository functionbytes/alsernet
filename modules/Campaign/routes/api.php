<?php

use Illuminate\Support\Facades\Route;
use Modules\Campaign\Http\Controllers\Api\AutomationController;
use Modules\Campaign\Http\Controllers\Api\CampaignController;
use Modules\Campaign\Http\Controllers\Api\MaillistController;
use Modules\Campaign\Http\Controllers\Api\SubscriberController;

/*
|--------------------------------------------------------------------------
| Campaign Module API Routes
|--------------------------------------------------------------------------
|
| All API routes for the Campaign module.
| Routes are protected by 'api' and 'auth:sanctum' middleware and prefixed with 'api'.
| This file is loaded via the Campaign\Providers\RouteServiceProvider.
|
| Feature Groups:
| - Campaigns (Email Campaign Management) - 9 routes
| - Automations (Automated Email Workflows) - 8 routes
| - Subscribers (Individual Subscriber Management) - 6 routes
| - Maillists (Subscriber List Management) - 9 routes
|
| Total Routes: 32
|
*/

Route::middleware(['api', 'auth:sanctum'])
    ->prefix('api')
    ->name('api.')
    ->group(function (): void {
        // ===================================================================
        // CAMPAIGNS ROUTES - Email campaign management via API (9 routes)
        // ===================================================================

        Route::group(['prefix' => 'campaigns', 'name' => 'campaigns.'], function (): void {
            // Campaign CRUD operations
            Route::get('/', [CampaignController::class, 'index'])->name('index');
            Route::post('/', [CampaignController::class, 'store'])->name('store');
            Route::get('/{uid}', [CampaignController::class, 'show'])->name('show');
            Route::put('/{uid}', [CampaignController::class, 'update'])->name('update');
            Route::delete('/{uid}', [CampaignController::class, 'delete'])->name('delete');

            // Campaign action routes
            Route::post('/{uid}/pause', [CampaignController::class, 'pause'])->name('pause');
            Route::post('/{uid}/run', [CampaignController::class, 'run'])->name('run');
            Route::post('/{uid}/resume', [CampaignController::class, 'resume'])->name('resume');
        });

        // ===================================================================
        // AUTOMATIONS ROUTES - Automated email workflows via API (8 routes)
        // ===================================================================

        Route::group(['prefix' => 'automations', 'name' => 'automations.'], function (): void {
            // Automation CRUD operations
            Route::get('/', [AutomationController::class, 'index'])->name('index');
            Route::post('/', [AutomationController::class, 'store'])->name('store');
            Route::get('/{uid}', [AutomationController::class, 'show'])->name('show');
            Route::put('/{uid}', [AutomationController::class, 'update'])->name('update');
            Route::delete('/{uid}', [AutomationController::class, 'delete'])->name('delete');

            // Automation action routes
            Route::post('/{uid}/execute', [AutomationController::class, 'execute'])->name('execute');
            Route::post('/{uid}/enable', [AutomationController::class, 'enable'])->name('enable');
            Route::post('/{uid}/disable', [AutomationController::class, 'disable'])->name('disable');
        });

        // ===================================================================
        // SUBSCRIBERS ROUTES - Individual subscriber management via API (6 routes)
        // ===================================================================

        Route::group(['prefix' => 'subscribers', 'name' => 'subscribers.'], function (): void {
            // Subscriber CRUD operations
            Route::get('/', [SubscriberController::class, 'index'])->name('index');
            Route::post('/', [SubscriberController::class, 'store'])->name('store');
            Route::get('/{uid}', [SubscriberController::class, 'show'])->name('show');
            Route::put('/{uid}', [SubscriberController::class, 'update'])->name('update');
            Route::delete('/{uid}', [SubscriberController::class, 'delete'])->name('delete');
            Route::get('/{uid}/logs', [SubscriberController::class, 'logs'])->name('logs');
        });

        // ===================================================================
        // MAILLISTS ROUTES - Subscriber list management via API (9 routes)
        // ===================================================================

        Route::group(['prefix' => 'maillists', 'name' => 'maillists.'], function (): void {
            // Maillist CRUD operations
            Route::get('/', [MaillistController::class, 'index'])->name('index');
            Route::post('/', [MaillistController::class, 'store'])->name('store');
            Route::get('/{uid}', [MaillistController::class, 'show'])->name('show');
            Route::put('/{uid}', [MaillistController::class, 'update'])->name('update');
            Route::delete('/{uid}', [MaillistController::class, 'delete'])->name('delete');

            // Maillist action routes
            Route::get('/{uid}/subscribers', [MaillistController::class, 'subscribers'])->name('subscribers');
            Route::post('/{uid}/verify', [MaillistController::class, 'verify'])->name('verify');
            Route::get('/{uid}/stats', [MaillistController::class, 'stats'])->name('stats');
        });
    });
