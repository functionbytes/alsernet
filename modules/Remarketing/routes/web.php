<?php

use Illuminate\Support\Facades\Route;
use Modules\Remarketing\Http\Controllers as Web;
use Modules\Remarketing\Http\Controllers\Manager\AutomationTriggersController;
use Modules\Remarketing\Http\Controllers\Public as PublicCtrl;
use Modules\Remarketing\Http\Controllers\Settings;

Route::middleware(['auth'])->group(function () {
    Route::prefix('panel/remarketing')
        ->name('remarketing.')
        ->group(function () {
            Route::get('/', [Web\DashboardController::class, 'index'])->name('dashboard');
            Route::get('/chart-data', [Web\DashboardController::class, 'chartData'])->name('dashboard.chart-data');

            Route::resource('stores', Web\StoreController::class)->except(['show']);
            Route::post('stores/{store}/sync', [Web\StoreController::class, 'sync'])->name('stores.sync');
            Route::get('stores/{store}/health', [Web\StoreController::class, 'health'])->name('stores.health');
            Route::get('stores/{store}/dns-check', [Web\StoreController::class, 'dnsCheck'])->name('stores.dns-check');
            Route::post('stores/verify', [Web\StoreController::class, 'verify'])->name('stores.verify');

            Route::get('customers', [Web\CustomerController::class, 'index'])->name('customers.index');
            Route::get('customers/{customer}', [Web\CustomerController::class, 'show'])->name('customers.show');
            Route::delete('customers/{customer}', [Web\CustomerController::class, 'destroy'])->name('customers.destroy');

            Route::get('products', [Web\ProductController::class, 'index'])->name('products.index');
            Route::get('carts', [Web\CartController::class, 'index'])->name('carts.index');

            Route::resource('segments', Web\SegmentController::class)->except(['show']);
            Route::get('segments/{segment}/preview', [Web\SegmentController::class, 'preview'])->name('segments.preview');

            // Engagement-derived audiences (require Engagement module active)
            Route::prefix('engagement-segments')->name('engagement-segments.')->group(function () {
                Route::get('status', [Web\EngagementSegmentController::class, 'status'])->name('status');
                Route::get('hot', [Web\EngagementSegmentController::class, 'hot'])->name('hot');
                Route::get('cart-abandoners', [Web\EngagementSegmentController::class, 'cartAbandoners'])->name('cart-abandoners');
                Route::get('by-event', [Web\EngagementSegmentController::class, 'byEvent'])->name('by-event');
            });

            Route::resource('campaigns', Web\CampaignController::class)->except(['show']);
            Route::post('campaigns/{campaign}/schedule', [Web\CampaignController::class, 'schedule'])->name('campaigns.schedule');
            Route::post('campaigns/{campaign}/send-test', [Web\CampaignController::class, 'sendTest'])->name('campaigns.sendTest');
            Route::post('campaigns/{campaign}/cancel', [Web\CampaignController::class, 'cancel'])->name('campaigns.cancel');
            Route::get('campaigns/{campaign}/preview', [Web\CampaignController::class, 'preview'])->name('campaigns.preview');

            Route::resource('automations', Web\AutomationController::class)->except(['show']);
            Route::post('automations/{automation}/activate', [Web\AutomationController::class, 'activate'])->name('automations.activate');
            Route::post('automations/{automation}/pause', [Web\AutomationController::class, 'pause'])->name('automations.pause');

            Route::resource('templates', Web\TemplateController::class)->except(['show']);
            Route::get('templates/{template}/preview', [Web\TemplateController::class, 'preview'])->name('templates.preview');

            Route::get('reports', [Web\ReportController::class, 'index'])->name('reports.index');
            Route::get('/reports/export-pdf', [Web\ReportController::class, 'exportPdf'])->name('reports.export-pdf');
            Route::get('/reports/chart-data', [Web\ReportController::class, 'chartData'])->name('reports.chart-data');
            Route::get('/reports/campaigns-data', [Web\ReportController::class, 'campaignsData'])->name('reports.campaigns-data');

            Route::get('triggers', [AutomationTriggersController::class, 'index'])->name('triggers.index');
        });

    Route::prefix('panel/settings/remarketing')
        ->name('settings.remarketing.')
        ->group(function () {
            Route::get('/', [Settings\SettingsController::class, 'general'])->name('general');
            Route::patch('/general', [Settings\SettingsController::class, 'updateGeneral'])->name('updateGeneral');
            Route::get('/consent', [Settings\SettingsController::class, 'consent'])->name('consent');
            Route::patch('/consent', [Settings\SettingsController::class, 'updateConsent'])->name('updateConsent');
            Route::get('/suppressions', [Settings\SettingsController::class, 'suppressions'])->name('suppressions');
            Route::get('/suppressions/export', [Settings\SettingsController::class, 'exportSuppressions'])->name('suppressions.export');
            Route::post('/suppressions/import', [Settings\SettingsController::class, 'importSuppressions'])->name('suppressions.import');
        });
});

Route::prefix('r')->group(function () {
    Route::middleware(['throttle:120,1'])->group(function () {
        Route::post('/track', [PublicCtrl\TrackController::class, 'track'])->name('remarketing.public.track');
    });

    Route::middleware(['throttle:30,1'])->group(function () {
        Route::match(['GET', 'POST'], '/unsubscribe/{token}', [PublicCtrl\UnsubscribeController::class, 'handle'])->name('remarketing.public.unsubscribe');
        Route::get('/preferences/{token}', [PublicCtrl\PreferencesController::class, 'show'])->name('remarketing.public.preferences.show');
        Route::post('/preferences/{token}', [PublicCtrl\PreferencesController::class, 'update'])->name('remarketing.public.preferences.update');
    });

    Route::get('/open/{messageId}.gif', [PublicCtrl\TrackingController::class, 'open'])
        ->where('messageId', '[0-9]+')
        ->name('remarketing.public.open');
    Route::get('/click/{messageId}/{linkHash}', [PublicCtrl\TrackingController::class, 'click'])
        ->where('messageId', '[0-9]+')
        ->name('remarketing.public.click');

    Route::prefix('webhooks')->group(function () {
        Route::post('/shopify/{storeToken}', [PublicCtrl\WebhookController::class, 'shopify'])->name('remarketing.public.webhook.shopify');
        Route::post('/woocommerce/{storeToken}', [PublicCtrl\WebhookController::class, 'woocommerce'])->name('remarketing.public.webhook.woocommerce');
        Route::post('/prestashop/{storeToken}', [PublicCtrl\WebhookController::class, 'prestashop'])->name('remarketing.public.webhook.prestashop');
        Route::post('/email-events/{provider}', [PublicCtrl\WebhookController::class, 'emailEvents'])->name('remarketing.public.webhook.emailEvents');
    });
});
