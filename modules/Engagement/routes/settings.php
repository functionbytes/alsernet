<?php

use Illuminate\Support\Facades\Route;
use Modules\Engagement\Http\Controllers\Settings\AuditLogController;
use Modules\Engagement\Http\Controllers\Settings\AutomationFlowController;
use Modules\Engagement\Http\Controllers\Settings\ConversionGoalController;
use Modules\Engagement\Http\Controllers\Settings\PersonalizationRuleController;
use Modules\Engagement\Http\Controllers\Settings\PlatformIntegrationController;
use Modules\Engagement\Http\Controllers\Settings\TriggerRuleController;
use Modules\Engagement\Http\Controllers\Settings\WebhookLogController;

/*
|--------------------------------------------------------------------------
| Engagement settings routes
|--------------------------------------------------------------------------
| Mounted with prefix `panel/settings/engagement` and name `settings.engagement.`
*/

Route::prefix('triggers')->name('triggers.')->group(function () {
    Route::get('/page', [TriggerRuleController::class, 'page'])->name('page');
    Route::get('/', [TriggerRuleController::class, 'index'])->name('index');
    Route::post('/', [TriggerRuleController::class, 'store'])->name('store');
    Route::put('/{triggerRule}', [TriggerRuleController::class, 'update'])->name('update');
    Route::delete('/{triggerRule}', [TriggerRuleController::class, 'destroy'])->name('destroy');
    Route::post('/bulk-action', [TriggerRuleController::class, 'bulkAction'])->name('bulk-action');
});

Route::prefix('personalizations')->name('personalizations.')->group(function () {
    Route::get('/page', [PersonalizationRuleController::class, 'page'])->name('page');
    Route::get('/', [PersonalizationRuleController::class, 'index'])->name('index');
    Route::post('/', [PersonalizationRuleController::class, 'store'])->name('store');
    Route::put('/{personalizationRule}', [PersonalizationRuleController::class, 'update'])->name('update');
    Route::delete('/{personalizationRule}', [PersonalizationRuleController::class, 'destroy'])->name('destroy');
    Route::post('/bulk-action', [PersonalizationRuleController::class, 'bulkAction'])->name('bulk-action');
});

Route::prefix('platforms')->name('platforms.')->group(function () {
    Route::get('/page', [PlatformIntegrationController::class, 'page'])->name('page');
    Route::get('/', [PlatformIntegrationController::class, 'index'])->name('index');
    Route::get('/{platformIntegration}', [PlatformIntegrationController::class, 'show'])->name('show');
    Route::post('/', [PlatformIntegrationController::class, 'store'])->name('store');
    Route::put('/{platformIntegration}', [PlatformIntegrationController::class, 'update'])->name('update');
    Route::post('/{platformIntegration}/rotate-secret', [PlatformIntegrationController::class, 'rotateSecret'])->name('rotate-secret');
    Route::delete('/{platformIntegration}', [PlatformIntegrationController::class, 'destroy'])->name('destroy');
});

Route::prefix('automation')->name('automation.')->group(function () {
    Route::get('/page', [AutomationFlowController::class, 'page'])->name('page');
    Route::get('/', [AutomationFlowController::class, 'index'])->name('index');
    Route::get('/{automationFlow}', [AutomationFlowController::class, 'show'])->name('show');
    Route::post('/', [AutomationFlowController::class, 'store'])->name('store');
    Route::put('/{automationFlow}', [AutomationFlowController::class, 'update'])->name('update');
    Route::delete('/{automationFlow}', [AutomationFlowController::class, 'destroy'])->name('destroy');
});

Route::prefix('goals')->name('goals.')->group(function () {
    Route::get('/page', [ConversionGoalController::class, 'page'])->name('page');
    Route::get('/', [ConversionGoalController::class, 'index'])->name('index');
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
