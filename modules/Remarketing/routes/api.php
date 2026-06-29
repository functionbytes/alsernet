<?php

use Illuminate\Support\Facades\Route;
use Modules\Remarketing\Http\Controllers\Api;

// El RouteServiceProvider del módulo ya envuelve estas rutas con prefix `api` y name `api.`.
// Aquí definimos solo el subprefix `remarketing` y los nombres relativos.
Route::middleware(['auth:sanctum', 'throttle:60,1'])
    ->prefix('remarketing')
    ->name('remarketing.')
    ->group(function () {
        // Stores
        Route::get('stores', [Api\StoreApiController::class, 'index'])->name('stores.index');
        Route::post('stores', [Api\StoreApiController::class, 'store'])->name('stores.store');
        Route::get('stores/{store}', [Api\StoreApiController::class, 'show'])->name('stores.show');
        Route::put('stores/{store}', [Api\StoreApiController::class, 'update'])->name('stores.update');
        Route::delete('stores/{store}', [Api\StoreApiController::class, 'destroy'])->name('stores.destroy');
        Route::post('stores/{store}/sync', [Api\StoreApiController::class, 'sync'])->name('stores.sync');
        Route::get('stores/{store}/health', [Api\StoreApiController::class, 'health'])->name('stores.health');

        // Customers
        Route::get('customers', [Api\CustomerApiController::class, 'index'])->name('customers.index');
        Route::get('customers/{customer}', [Api\CustomerApiController::class, 'show'])->name('customers.show');
        Route::put('customers/{customer}', [Api\CustomerApiController::class, 'update'])->name('customers.update');
        Route::delete('customers/{customer}', [Api\CustomerApiController::class, 'destroy'])->name('customers.destroy');
        Route::get('customers/{customer}/events', [Api\CustomerApiController::class, 'events'])->name('customers.events');

        // Segments
        Route::apiResource('segments', Api\SegmentApiController::class);
        Route::get('segments/{segment}/preview', [Api\SegmentApiController::class, 'preview'])->name('segments.preview');

        // Campaigns
        Route::apiResource('campaigns', Api\CampaignApiController::class);
        Route::post('campaigns/{campaign}/schedule', [Api\CampaignApiController::class, 'schedule'])->name('campaigns.schedule');
        Route::post('campaigns/{campaign}/send-test', [Api\CampaignApiController::class, 'sendTest'])->name('campaigns.sendTest');
        Route::post('campaigns/{campaign}/cancel', [Api\CampaignApiController::class, 'cancel'])->name('campaigns.cancel');
        Route::get('campaigns/{campaign}/stats', [Api\CampaignApiController::class, 'stats'])->name('campaigns.stats');

        // Automations
        Route::apiResource('automations', Api\AutomationApiController::class);
        Route::post('automations/{automation}/activate', [Api\AutomationApiController::class, 'activate'])->name('automations.activate');
        Route::post('automations/{automation}/pause', [Api\AutomationApiController::class, 'pause'])->name('automations.pause');

        // Templates
        Route::apiResource('templates', Api\TemplateApiController::class);
        Route::post('templates/{template}/preview', [Api\TemplateApiController::class, 'preview'])->name('templates.preview');

        // Suppressions
        Route::get('suppressions', [Api\SuppressionApiController::class, 'index'])->name('suppressions.index');
        Route::post('suppressions', [Api\SuppressionApiController::class, 'store'])->name('suppressions.store');
        Route::delete('suppressions/{suppression}', [Api\SuppressionApiController::class, 'destroy'])->name('suppressions.destroy');

        // DSR
        Route::post('dsr/export', [Api\DsrApiController::class, 'export'])->name('dsr.export');
        Route::post('dsr/delete', [Api\DsrApiController::class, 'delete'])->name('dsr.delete');
    });
