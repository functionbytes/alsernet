<?php

use Illuminate\Support\Facades\Route;
use Modules\Engagement\Http\Controllers\Settings\AbTestController;
use Modules\Engagement\Http\Controllers\Settings\AuditLogController;
use Modules\Engagement\Http\Controllers\Settings\AutomationFlowController;
use Modules\Engagement\Http\Controllers\Settings\ConversionGoalController;
use Modules\Engagement\Http\Controllers\Settings\EmailCampaignController;
use Modules\Engagement\Http\Controllers\Settings\PersonalizationRuleController;
use Modules\Engagement\Http\Controllers\Settings\PlatformIntegrationController;
use Modules\Engagement\Http\Controllers\Settings\SegmentController;
use Modules\Engagement\Http\Controllers\Settings\SettingsIndexController;
use Modules\Engagement\Http\Controllers\Settings\TriggerRuleController;
use Modules\Engagement\Http\Controllers\Settings\WebChannelController;
use Modules\Engagement\Http\Controllers\Settings\WebhookLogController;

/*
|--------------------------------------------------------------------------
| Engagement settings routes
|--------------------------------------------------------------------------
| Mounted with prefix `panel/settings/engagement` and name `settings.engagement.`
*/

Route::get('/', [SettingsIndexController::class, 'index'])->name('index');

Route::prefix('triggers')->name('triggers.')->group(function () {
    Route::get('/page', [TriggerRuleController::class, 'page'])->name('page');
    Route::get('/', [TriggerRuleController::class, 'index'])->name('index');
    Route::get('/trashed', [TriggerRuleController::class, 'trashed'])->name('trashed');
    Route::post('/{id}/restore', [TriggerRuleController::class, 'restore'])->name('restore');
    Route::post('/', [TriggerRuleController::class, 'store'])->name('store');
    Route::put('/{triggerRule}', [TriggerRuleController::class, 'update'])->name('update');
    Route::delete('/{triggerRule}', [TriggerRuleController::class, 'destroy'])->name('destroy');
    Route::post('/bulk-action', [TriggerRuleController::class, 'bulkAction'])->name('bulk-action');
});

Route::prefix('personalizations')->name('personalizations.')->group(function () {
    Route::get('/page', [PersonalizationRuleController::class, 'page'])->name('page');
    Route::get('/', [PersonalizationRuleController::class, 'index'])->name('index');
    Route::get('/trashed', [PersonalizationRuleController::class, 'trashed'])->name('trashed');
    Route::post('/{id}/restore', [PersonalizationRuleController::class, 'restore'])->name('restore');
    Route::post('/', [PersonalizationRuleController::class, 'store'])->name('store');
    Route::put('/{personalizationRule}', [PersonalizationRuleController::class, 'update'])->name('update');
    Route::delete('/{personalizationRule}', [PersonalizationRuleController::class, 'destroy'])->name('destroy');
    Route::post('/bulk-action', [PersonalizationRuleController::class, 'bulkAction'])->name('bulk-action');
});

Route::prefix('platforms')->name('platforms.')->group(function () {
    Route::get('/page', [PlatformIntegrationController::class, 'page'])->name('page');
    Route::get('/', [PlatformIntegrationController::class, 'index'])->name('index');
    Route::get('/trashed', [PlatformIntegrationController::class, 'trashed'])->name('trashed');
    Route::post('/{id}/restore', [PlatformIntegrationController::class, 'restore'])->name('restore');
    Route::get('/{platformIntegration}', [PlatformIntegrationController::class, 'show'])->name('show');
    Route::post('/', [PlatformIntegrationController::class, 'store'])->name('store');
    Route::put('/{platformIntegration}', [PlatformIntegrationController::class, 'update'])->name('update');
    Route::post('/{platformIntegration}/rotate-secret', [PlatformIntegrationController::class, 'rotateSecret'])->name('rotate-secret');
    Route::delete('/{platformIntegration}', [PlatformIntegrationController::class, 'destroy'])->name('destroy');
});

Route::prefix('automation')->name('automation.')->group(function () {
    Route::get('/page', [AutomationFlowController::class, 'page'])->name('page');
    Route::get('/', [AutomationFlowController::class, 'index'])->name('index');
    Route::get('/trashed', [AutomationFlowController::class, 'trashed'])->name('trashed');
    Route::post('/{id}/restore', [AutomationFlowController::class, 'restore'])->name('restore');
    Route::get('/{automationFlow}', [AutomationFlowController::class, 'show'])->name('show');
    Route::post('/', [AutomationFlowController::class, 'store'])->name('store');
    Route::put('/{automationFlow}', [AutomationFlowController::class, 'update'])->name('update');
    Route::delete('/{automationFlow}', [AutomationFlowController::class, 'destroy'])->name('destroy');
});

Route::prefix('goals')->name('goals.')->group(function () {
    Route::get('/page', [ConversionGoalController::class, 'page'])->name('page');
    Route::get('/', [ConversionGoalController::class, 'index'])->name('index');
    Route::get('/trashed', [ConversionGoalController::class, 'trashed'])->name('trashed');
    Route::post('/{id}/restore', [ConversionGoalController::class, 'restore'])->name('restore');
    Route::post('/', [ConversionGoalController::class, 'store'])->name('store');
    Route::put('/{conversionGoal}', [ConversionGoalController::class, 'update'])->name('update');
    Route::get('/{conversionGoal}/funnel', [ConversionGoalController::class, 'funnel'])->name('funnel');
    Route::delete('/{conversionGoal}', [ConversionGoalController::class, 'destroy'])->name('destroy');
});

Route::prefix('webhook-logs')->name('webhook-logs.')->group(function () {
    Route::get('/page', [WebhookLogController::class, 'page'])->name('page');
    Route::get('/', [WebhookLogController::class, 'index'])->name('index');
    Route::get('/{webhookLog}', [WebhookLogController::class, 'show'])->name('show');
    Route::post('/{webhookLog}/retry', [WebhookLogController::class, 'retry'])->name('retry');
});

Route::prefix('audit-logs')->name('audit-logs.')->group(function () {
    Route::get('/page', [AuditLogController::class, 'page'])->name('page');
    Route::get('/', [AuditLogController::class, 'index'])->name('index');
});

Route::prefix('segments')->name('segments.')->group(function () {
    Route::get('/page', [SegmentController::class, 'page'])->name('page');
    Route::get('/', [SegmentController::class, 'index'])->name('index');
    Route::get('/trashed', [SegmentController::class, 'trashed'])->name('trashed');
    Route::post('/{id}/restore', [SegmentController::class, 'restore'])->name('restore');
    Route::post('/', [SegmentController::class, 'store'])->name('store');
    Route::put('/{segment}', [SegmentController::class, 'update'])->name('update');
    Route::delete('/{segment}', [SegmentController::class, 'destroy'])->name('destroy');
});

Route::prefix('ab-tests')->name('ab-tests.')->group(function () {
    Route::get('/page', [AbTestController::class, 'page'])->name('page');
    Route::get('/', [AbTestController::class, 'index'])->name('index');
    Route::get('/trashed', [AbTestController::class, 'trashed'])->name('trashed');
    Route::post('/{id}/restore', [AbTestController::class, 'restore'])->name('restore');
    Route::get('/{abTest}', [AbTestController::class, 'show'])->name('show');
    Route::post('/', [AbTestController::class, 'store'])->name('store');
    Route::put('/{abTest}', [AbTestController::class, 'update'])->name('update');
    Route::post('/{abTest}/start', [AbTestController::class, 'start'])->name('start');
    Route::post('/{abTest}/pause', [AbTestController::class, 'pause'])->name('pause');
    Route::delete('/{abTest}', [AbTestController::class, 'destroy'])->name('destroy');
});

Route::prefix('email-campaigns')->name('email-campaigns.')->group(function () {
    Route::get('/page', [EmailCampaignController::class, 'page'])->name('page');
    Route::get('/', [EmailCampaignController::class, 'index'])->name('index');
    Route::get('/trashed', [EmailCampaignController::class, 'trashed'])->name('trashed');
    Route::post('/{id}/restore', [EmailCampaignController::class, 'restore'])->name('restore');
    Route::post('/', [EmailCampaignController::class, 'store'])->name('store');
    Route::put('/{emailCampaign}', [EmailCampaignController::class, 'update'])->name('update');
    Route::post('/{emailCampaign}/send', [EmailCampaignController::class, 'send'])->name('send');
    Route::post('/{emailCampaign}/sync-stats', [EmailCampaignController::class, 'syncStats'])->name('sync-stats');
    Route::delete('/{emailCampaign}', [EmailCampaignController::class, 'destroy'])->name('destroy');
});

Route::prefix('web-channels')->name('web-channels.')->group(function () {
    Route::get('/page', [WebChannelController::class, 'page'])->name('page');
    Route::get('/', [WebChannelController::class, 'index'])->name('index');
    Route::get('/trashed', [WebChannelController::class, 'trashed'])->name('trashed');
    Route::post('/{id}/restore', [WebChannelController::class, 'restore'])->name('restore');
    Route::post('/', [WebChannelController::class, 'store'])->name('store');
    Route::put('/{webChannel}', [WebChannelController::class, 'update'])->name('update');
    Route::delete('/{webChannel}', [WebChannelController::class, 'destroy'])->name('destroy');
});
