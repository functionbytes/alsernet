<?php

use Illuminate\Support\Facades\Route;
use Modules\Mailrelay\Http\Controllers\Managers\CampaignManagerController;
use Modules\Mailrelay\Http\Controllers\Managers\MailProviderController;
use Modules\Mailrelay\Http\Controllers\Settings\ApiSettingsController;
use Modules\Mailrelay\Http\Controllers\Settings\AutomationController;
use Modules\Mailrelay\Http\Controllers\Settings\ComponentController;
use Modules\Mailrelay\Http\Controllers\Settings\CustomFieldController;
use Modules\Mailrelay\Http\Controllers\Settings\EndpointController;
use Modules\Mailrelay\Http\Controllers\Settings\GeneralSettingsController;
use Modules\Mailrelay\Http\Controllers\Settings\GroupController;
use Modules\Mailrelay\Http\Controllers\Settings\PermissionController;
use Modules\Mailrelay\Http\Controllers\Settings\TemplateController;
use Modules\Mailrelay\Http\Controllers\Settings\VariableController;
use Modules\Mailrelay\Http\Controllers\Settings\WebhookController;
use Modules\Mailrelay\Http\Controllers\Web\CampaignWebController;
use Modules\Mailrelay\Http\Controllers\Web\DashboardController;
use Modules\Mailrelay\Http\Controllers\Web\ImportWebController;
use Modules\Mailrelay\Http\Controllers\Web\ListWebController;
// ✨ Multi-Provider Controllers (v2.0)
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
        // MAIL PROVIDERS (Multi-Provider v2.0) ✨
        // ----------------------------------------------------------------
        Route::prefix('providers')->name('providers.')->group(function () {
            Route::get('/', [MailProviderController::class, 'index'])->name('index');
            Route::get('/create', [MailProviderController::class, 'create'])->name('create');
            Route::post('/', [MailProviderController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [MailProviderController::class, 'edit'])->name('edit');
            Route::put('/{id}', [MailProviderController::class, 'update'])->name('update');
            Route::delete('/{id}', [MailProviderController::class, 'destroy'])->name('destroy');

            // Provider actions
            Route::post('/{id}/test', [MailProviderController::class, 'test'])->name('test');
            Route::post('/{id}/set-default', [MailProviderController::class, 'setDefault'])->name('set-default');
            Route::post('/{id}/toggle-active', [MailProviderController::class, 'toggleActive'])->name('toggle-active');
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

            // New template actions
            Route::post('/{template}/preview-ajax', [TemplateController::class, 'previewAjax'])->name('preview-ajax');
            Route::get('/{template}/variables', [TemplateController::class, 'variables'])->name('variables');
            Route::post('/format-html', [TemplateController::class, 'formatHtml'])->name('format-html');
            Route::post('/{template}/send-test', [TemplateController::class, 'sendTest'])->name('send-test');
            Route::patch('/{template}/toggle-status', [TemplateController::class, 'toggleStatus'])->name('toggle-status');
        });

        // ----------------------------------------------------------------
        // LAYOUTS & COMPONENTS
        // ----------------------------------------------------------------
        Route::prefix('components')->name('components.')->group(function () {
            Route::get('/', [ComponentController::class, 'index'])->name('index');
            Route::get('/create', [ComponentController::class, 'create'])->name('create');
            Route::post('/', [ComponentController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [ComponentController::class, 'edit'])->name('edit');
            Route::put('/{id}', [ComponentController::class, 'update'])->name('update');
            Route::delete('/{id}', [ComponentController::class, 'destroy'])->name('destroy');

            // Component actions
            Route::post('/{id}/preview-ajax', [ComponentController::class, 'previewAjax'])->name('preview-ajax');
            Route::patch('/{id}/toggle-status', [ComponentController::class, 'toggleStatus'])->name('toggle-status');
            Route::post('/{id}/duplicate', [ComponentController::class, 'duplicate'])->name('duplicate');
            Route::post('/{id}/set-default', [ComponentController::class, 'setDefault'])->name('set-default');

            // API endpoints
            Route::get('/by-type/{type}', [ComponentController::class, 'getByType'])->name('by-type');
        });

        // ----------------------------------------------------------------
        // VARIABLES
        // ----------------------------------------------------------------
        Route::prefix('variables')->name('variables.')->group(function () {
            Route::get('/', [VariableController::class, 'index'])->name('index');
            Route::get('/create', [VariableController::class, 'create'])->name('create');
            Route::post('/', [VariableController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [VariableController::class, 'edit'])->name('edit');
            Route::put('/{id}', [VariableController::class, 'update'])->name('update');
            Route::delete('/{id}', [VariableController::class, 'destroy'])->name('destroy');

            // Variable actions
            Route::patch('/{id}/toggle-status', [VariableController::class, 'toggleStatus'])->name('toggle-status');

            // API endpoints
            Route::get('/by-category/{category}', [VariableController::class, 'getByCategory'])->name('by-category');
            Route::get('/by-module/{module}', [VariableController::class, 'getByModule'])->name('by-module');
            Route::get('/all', [VariableController::class, 'getAll'])->name('all');
            Route::get('/grouped', [VariableController::class, 'getGrouped'])->name('grouped');
        });

        // ----------------------------------------------------------------
        // ENDPOINTS
        // ----------------------------------------------------------------
        Route::prefix('endpoints')->name('endpoints.')->group(function () {
            Route::get('/', [EndpointController::class, 'index'])->name('index');
            Route::get('/create', [EndpointController::class, 'create'])->name('create');
            Route::post('/', [EndpointController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [EndpointController::class, 'edit'])->name('edit');
            Route::put('/{id}', [EndpointController::class, 'update'])->name('update');
            Route::delete('/{id}', [EndpointController::class, 'destroy'])->name('destroy');

            // Endpoint actions
            Route::post('/{id}/regenerate-key', [EndpointController::class, 'regenerateKey'])->name('regenerate-key');
            Route::get('/{id}/logs', [EndpointController::class, 'logs'])->name('logs');
            Route::delete('/{id}/logs', [EndpointController::class, 'clearLogs'])->name('clear-logs');
            Route::patch('/{id}/toggle-status', [EndpointController::class, 'toggleStatus'])->name('toggle-status');
            Route::post('/{id}/test', [EndpointController::class, 'test'])->name('test');
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
    // MANAGER ROUTES - /managers/mailrelay (Multi-Provider v2.0) ✨
    // ====================================================================
    Route::prefix('managers/mailrelay')->name('managers.mailrelay.')->group(function () {

        // ----------------------------------------------------------------
        // MAIL PROVIDERS MANAGEMENT
        // ----------------------------------------------------------------
        Route::prefix('providers')->name('providers.')->group(function () {
            Route::get('/', [MailProviderController::class, 'index'])->name('index');
            Route::get('/create', [MailProviderController::class, 'create'])->name('create');
            Route::post('/', [MailProviderController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [MailProviderController::class, 'edit'])->name('edit');
            Route::put('/{id}', [MailProviderController::class, 'update'])->name('update');
            Route::delete('/{id}', [MailProviderController::class, 'destroy'])->name('destroy');

            // Provider actions
            Route::post('/{id}/test', [MailProviderController::class, 'test'])->name('test');
            Route::post('/{id}/set-default', [MailProviderController::class, 'setDefault'])->name('set-default');
            Route::post('/{id}/toggle-active', [MailProviderController::class, 'toggleActive'])->name('toggle-active');
        });

        // ----------------------------------------------------------------
        // CAMPAIGNS MANAGEMENT (Mailer Integration)
        // ----------------------------------------------------------------
        Route::prefix('campaigns')->name('campaigns.')->group(function () {
            Route::get('/', [CampaignManagerController::class, 'index'])->name('index');
            Route::get('/create', [CampaignManagerController::class, 'create'])->name('create');
            Route::post('/', [CampaignManagerController::class, 'store'])->name('store');
            Route::get('/{id}', [CampaignManagerController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [CampaignManagerController::class, 'edit'])->name('edit');
            Route::put('/{id}', [CampaignManagerController::class, 'update'])->name('update');
            Route::delete('/{id}', [CampaignManagerController::class, 'destroy'])->name('destroy');

            // Campaign actions
            Route::post('/{id}/duplicate', [CampaignManagerController::class, 'duplicate'])->name('duplicate');
            Route::get('/{id}/preview', [CampaignManagerController::class, 'preview'])->name('preview');
            Route::post('/{id}/send-test', [CampaignManagerController::class, 'sendTest'])->name('send-test');
            Route::post('/{id}/send', [CampaignManagerController::class, 'send'])->name('send');
            Route::post('/{id}/schedule', [CampaignManagerController::class, 'schedule'])->name('schedule');
        });

    }); // Cierra Route::prefix('managers/mailrelay')

    // ====================================================================
    // OPERATIONAL ROUTES - /mailrelay
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
