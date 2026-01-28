<?php

use Illuminate\Support\Facades\Route;
use Modules\Mailrelay\Http\Controllers\Settings\ApiSettingsController;
use Modules\Mailrelay\Http\Controllers\Settings\AutomationController;
use Modules\Mailrelay\Http\Controllers\Settings\CustomFieldController;
use Modules\Mailrelay\Http\Controllers\Settings\GeneralSettingsController;
use Modules\Mailrelay\Http\Controllers\Settings\GroupController;
use Modules\Mailrelay\Http\Controllers\Settings\PermissionController;
use Modules\Mailrelay\Http\Controllers\Settings\TemplateController;
use Modules\Mailrelay\Http\Controllers\Settings\WebhookController;
use Modules\Mailrelay\Http\Controllers\Web\CampaignWebController;
use Modules\Mailrelay\Http\Controllers\Web\DashboardController;
use Modules\Mailrelay\Http\Controllers\Web\ImportWebController;
use Modules\Mailrelay\Http\Controllers\Web\ListWebController;
use Modules\Mailrelay\Http\Controllers\Web\SubscriberController;
use Modules\Mailrelay\Http\Controllers\Web\ValidationController;


// ====================================================================
// CONFIGURATION ROUTES - /settings/mailrelay (Admin only)
// ====================================================================
Route::middleware(['web', 'auth'])
    ->prefix('mailrelay/setting')
    ->name('settings.mailrelay.')
    ->group(function () {


        // ----------------------------------------------------------------
        // GENERAL SETTINGS
        // ----------------------------------------------------------------
        Route::prefix('general')->name('general.')->group(function () {
            Route::get('/', [GeneralSettingsController::class, 'index'])->name('index');
            Route::patch('/', [GeneralSettingsController::class, 'update'])->name('update');
        });

        // ----------------------------------------------------------------
        // API CONFIGURATION
        // ----------------------------------------------------------------
        Route::prefix('api')->name('api.')->group(function () {
            Route::get('/', [ApiSettingsController::class, 'index'])->name('index');
            Route::post('/test-connection', [ApiSettingsController::class, 'testConnection'])->name('test-connection');
            Route::patch('/', [ApiSettingsController::class, 'update'])->name('update');
        });

        // ----------------------------------------------------------------
        // EMAIL TEMPLATES
        // ----------------------------------------------------------------
        Route::prefix('templates')->name('templates.')->group(function () {
            Route::get('/', [TemplateController::class, 'index'])->name('index');
            Route::get('/create', [TemplateController::class, 'create'])->name('create');
            Route::post('/', [TemplateController::class, 'store'])->name('store');
            Route::get('/{template}', [TemplateController::class, 'show'])->name('show');
            Route::get('/{template}/edit', [TemplateController::class, 'edit'])->name('edit');
            Route::put('/{template}', [TemplateController::class, 'update'])->name('update');
            Route::delete('/{template}', [TemplateController::class, 'destroy'])->name('destroy');
            Route::post('/{template}/duplicate', [TemplateController::class, 'duplicate'])->name('duplicate');
        });

        // ----------------------------------------------------------------
        // SUBSCRIBER GROUPS
        // ----------------------------------------------------------------
        Route::prefix('groups')->name('groups.')->group(function () {
            Route::get('/', [GroupController::class, 'index'])->name('index');
            Route::get('/create', [GroupController::class, 'create'])->name('create');
            Route::post('/', [GroupController::class, 'store'])->name('store');
            Route::get('/{group}/edit', [GroupController::class, 'edit'])->name('edit');
            Route::put('/{group}', [GroupController::class, 'update'])->name('update');
            Route::delete('/{group}', [GroupController::class, 'destroy'])->name('destroy');
            Route::post('/{group}/sync', [GroupController::class, 'sync'])->name('sync');
        });

        // ----------------------------------------------------------------
        // CUSTOM FIELDS
        // ----------------------------------------------------------------
        Route::prefix('custom-fields')->name('custom-fields.')->group(function () {
            Route::get('/', [CustomFieldController::class, 'index'])->name('index');
            Route::get('/create', [CustomFieldController::class, 'create'])->name('create');
            Route::post('/', [CustomFieldController::class, 'store'])->name('store');
            Route::get('/{field}/edit', [CustomFieldController::class, 'edit'])->name('edit');
            Route::put('/{field}', [CustomFieldController::class, 'update'])->name('update');
            Route::delete('/{field}', [CustomFieldController::class, 'destroy'])->name('destroy');
        });

        // ----------------------------------------------------------------
        // AUTOMATIONS
        // ----------------------------------------------------------------
        Route::prefix('automations')->name('automations.')->group(function () {
            Route::get('/', [AutomationController::class, 'index'])->name('index');
            Route::get('/create', [AutomationController::class, 'create'])->name('create');
            Route::post('/', [AutomationController::class, 'store'])->name('store');
            Route::get('/{automation}/edit', [AutomationController::class, 'edit'])->name('edit');
            Route::put('/{automation}', [AutomationController::class, 'update'])->name('update');
            Route::delete('/{automation}', [AutomationController::class, 'destroy'])->name('destroy');
            Route::patch('/{automation}/toggle', [AutomationController::class, 'toggle'])->name('toggle');
        });

        // ----------------------------------------------------------------
        // WEBHOOKS
        // ----------------------------------------------------------------
        Route::prefix('webhooks')->name('webhooks.')->group(function () {
            Route::get('/', [WebhookController::class, 'index'])->name('index');
            Route::get('/create', [WebhookController::class, 'create'])->name('create');
            Route::post('/', [WebhookController::class, 'store'])->name('store');
            Route::get('/{webhook}/edit', [WebhookController::class, 'edit'])->name('edit');
            Route::put('/{webhook}', [WebhookController::class, 'update'])->name('update');
            Route::delete('/{webhook}', [WebhookController::class, 'destroy'])->name('destroy');
            Route::post('/{webhook}/test', [WebhookController::class, 'test'])->name('test');
        });

        // ----------------------------------------------------------------
        // EMAIL VALIDATION (Settings)
        // ----------------------------------------------------------------
        Route::prefix('validation')->name('validation.')->group(function () {
            Route::get('/test', [ValidationController::class, 'test'])->name('test');
            Route::post('/validate-email', [ValidationController::class, 'validateEmail'])->name('validate-email');
            Route::post('/validate-bulk', [ValidationController::class, 'validateBulk'])->name('validate-bulk');
            Route::get('/history', [ValidationController::class, 'history'])->name('history');
            Route::get('/statistics', [ValidationController::class, 'statistics'])->name('statistics');
        });

        // ----------------------------------------------------------------
        // PERMISSIONS
        // ----------------------------------------------------------------
        Route::prefix('permissions')->name('permissions.')->group(function () {
            Route::get('/', [PermissionController::class, 'index'])->name('index');
            Route::post('/sync', [PermissionController::class, 'sync'])->name('sync');
            Route::post('/assign', [PermissionController::class, 'assign'])->name('assign');
        });
    }); // Cierra Route::prefix('settings/mailrelay')



Route::middleware(['web', 'auth'])->group(function () {

    // ====================================================================
    // OPERATIONAL ROUTES - /documents
    // ====================================================================
    Route::prefix('mailrelay')->name('mailrelay.')->group(function () {
        // Dashboard
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

        // ----------------------------------------------------------------
        // CAMPAIGNS
        // ----------------------------------------------------------------
        Route::prefix('campaigns')->name('campaigns.')->group(function () {
            Route::get('/', [CampaignWebController::class, 'index'])->name('index');
            Route::get('/create', [CampaignWebController::class, 'create'])->name('create');
            Route::post('/', [CampaignWebController::class, 'store'])->name('store');
            Route::get('/{campaign}', [CampaignWebController::class, 'show'])->name('show');
            Route::get('/{campaign}/edit', [CampaignWebController::class, 'edit'])->name('edit');
            Route::put('/{campaign}', [CampaignWebController::class, 'update'])->name('update');
            Route::delete('/{campaign}', [CampaignWebController::class, 'destroy'])->name('destroy');

            // Campaign actions
            Route::post('/{campaign}/send', [CampaignWebController::class, 'send'])->name('send');
            Route::post('/{campaign}/test', [CampaignWebController::class, 'sendTest'])->name('test');
            Route::post('/{campaign}/duplicate', [CampaignWebController::class, 'duplicate'])->name('duplicate');
            Route::get('/{campaign}/analytics', [CampaignWebController::class, 'analytics'])->name('analytics');
            Route::get('/{campaign}/preview', [CampaignWebController::class, 'preview'])->name('preview');
        });

        // ----------------------------------------------------------------
        // SUBSCRIBERS
        // ----------------------------------------------------------------
        Route::prefix('subscribers')->name('subscribers.')->group(function () {
            Route::get('/', [SubscriberController::class, 'index'])->name('index');
            Route::get('/create', [SubscriberController::class, 'create'])->name('create');
            Route::post('/', [SubscriberController::class, 'store'])->name('store');
            Route::get('/{subscriber}', [SubscriberController::class, 'show'])->name('show');
            Route::get('/{subscriber}/edit', [SubscriberController::class, 'edit'])->name('edit');
            Route::put('/{subscriber}', [SubscriberController::class, 'update'])->name('update');
            Route::delete('/{subscriber}', [SubscriberController::class, 'destroy'])->name('destroy');

            // Subscriber actions
            Route::post('/{subscriber}/sync', [SubscriberController::class, 'sync'])->name('sync');
            Route::post('/{subscriber}/unsubscribe', [SubscriberController::class, 'unsubscribe'])->name('unsubscribe');
            Route::post('/{subscriber}/resubscribe', [SubscriberController::class, 'resubscribe'])->name('resubscribe');
            Route::get('/{subscriber}/history', [SubscriberController::class, 'history'])->name('history');

            // Bulk actions
            Route::post('/bulk-import', [SubscriberController::class, 'bulkImport'])->name('bulk-import');
            Route::post('/bulk-delete', [SubscriberController::class, 'bulkDelete'])->name('bulk-delete');
            Route::post('/bulk-sync', [SubscriberController::class, 'bulkSync'])->name('bulk-sync');
            Route::get('/export', [SubscriberController::class, 'export'])->name('export');
        });

        // ----------------------------------------------------------------
        // IMPORTS
        // ----------------------------------------------------------------
        Route::prefix('imports')->name('imports.')->group(function () {
            Route::get('/', [ImportWebController::class, 'index'])->name('index');
            Route::get('/create', [ImportWebController::class, 'create'])->name('create');
            Route::post('/', [ImportWebController::class, 'store'])->name('store');
            Route::get('/{import}', [ImportWebController::class, 'show'])->name('show');
            Route::delete('/{import}', [ImportWebController::class, 'destroy'])->name('destroy');

            // Import actions
            Route::post('/{import}/process', [ImportWebController::class, 'process'])->name('process');
            Route::post('/{import}/cancel', [ImportWebController::class, 'cancel'])->name('cancel');
            Route::get('/{import}/download-errors', [ImportWebController::class, 'downloadErrors'])->name('download-errors');
            Route::get('/{import}/download-template', [ImportWebController::class, 'downloadTemplate'])->name('download-template');
        });

        // ----------------------------------------------------------------
        // LISTS
        // ----------------------------------------------------------------
        Route::prefix('lists')->name('lists.')->group(function () {
            Route::get('/', [ListWebController::class, 'index'])->name('index');
            Route::get('/create', [ListWebController::class, 'create'])->name('create');
            Route::post('/', [ListWebController::class, 'store'])->name('store');
            Route::get('/{list}', [ListWebController::class, 'show'])->name('show');
            Route::get('/{list}/edit', [ListWebController::class, 'edit'])->name('edit');
            Route::put('/{list}', [ListWebController::class, 'update'])->name('update');
            Route::delete('/{list}', [ListWebController::class, 'destroy'])->name('destroy');
        });

        // ----------------------------------------------------------------
        // VALIDATION
        // ----------------------------------------------------------------
        Route::prefix('validation')->name('validation.')->group(function () {
            Route::get('/test', [ValidationController::class, 'test'])->name('test');
            Route::post('/validate-email', [ValidationController::class, 'validateEmail'])->name('validate-email');
            Route::post('/validate-bulk', [ValidationController::class, 'validateBulk'])->name('validate-bulk');
            Route::get('/history', [ValidationController::class, 'history'])->name('history');
            Route::get('/statistics', [ValidationController::class, 'statistics'])->name('statistics');
        });

    }); // Cierra Route::prefix('mailrelay')
}); // Cierra Route::middleware(['web', 'auth'])
