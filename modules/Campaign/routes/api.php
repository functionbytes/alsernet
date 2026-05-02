<?php

use Illuminate\Support\Facades\Route;
use Modules\Campaign\Http\Controllers\Api\AutomationController;
use Modules\Campaign\Http\Controllers\Api\AutomationRuleController;
use Modules\Campaign\Http\Controllers\Api\BlacklistController;
use Modules\Campaign\Http\Controllers\Api\CampaignBackupController;
use Modules\Campaign\Http\Controllers\Api\CampaignController;
use Modules\Campaign\Http\Controllers\Api\CampaignDuplicateController;
use Modules\Campaign\Http\Controllers\Api\CampaignExportController;
use Modules\Campaign\Http\Controllers\Api\CampaignStatsController;
use Modules\Campaign\Http\Controllers\Api\CampaignTestController;
use Modules\Campaign\Http\Controllers\Api\CohortReportController;
use Modules\Campaign\Http\Controllers\Api\DuplicateSubscriberController;
use Modules\Campaign\Http\Controllers\Api\FieldController;
use Modules\Campaign\Http\Controllers\Api\GeoReportController;
use Modules\Campaign\Http\Controllers\Api\ImportDryRunController;
use Modules\Campaign\Http\Controllers\Api\MaillistController;
use Modules\Campaign\Http\Controllers\Api\SegmentController;
use Modules\Campaign\Http\Controllers\Api\SenderDomainController;
use Modules\Campaign\Http\Controllers\Api\SendingServerTestController;
use Modules\Campaign\Http\Controllers\Api\SubscriberBulkController;
use Modules\Campaign\Http\Controllers\Api\SubscriberController;
use Modules\Campaign\Http\Controllers\Api\SubscriberExportController;
use Modules\Campaign\Http\Controllers\Api\SubscriberSearchController;
use Modules\Campaign\Http\Controllers\Api\SubscriberTimelineController;
use Modules\Campaign\Http\Controllers\Api\SuppressionListController;
use Modules\Campaign\Http\Controllers\Api\TemplateController;
use Modules\Campaign\Http\Controllers\Api\WebhookController;
use Modules\Campaign\Http\Controllers\Api\WebhookLogController;

/*
|--------------------------------------------------------------------------
| Campaign Module API Routes
|--------------------------------------------------------------------------
|
| Cargado por CampaignServiceProvider con prefijo 'api/campaign' y
| middleware ['api', 'auth:sanctum', 'throttle:60,1'].
|
*/

// CAMPAIGNS
Route::group(['prefix' => 'campaigns', 'as' => 'campaigns.'], function (): void {
    Route::get('/', [CampaignController::class, 'index'])->name('index');
    Route::get('/search', [CampaignController::class, 'search'])->name('search');
    Route::post('/', [CampaignController::class, 'store'])->name('store');
    Route::get('/{uid}', [CampaignController::class, 'show'])->name('show');
    Route::put('/{uid}', [CampaignController::class, 'update'])->name('update');
    Route::delete('/{uid}', [CampaignController::class, 'delete'])->name('delete');

    Route::post('/{uid}/pause', [CampaignController::class, 'pause'])->name('pause');
    Route::post('/{uid}/run', [CampaignController::class, 'run'])->name('run');
    Route::post('/{uid}/resume', [CampaignController::class, 'resume'])->name('resume');
    Route::post('/{uid}/duplicate', [CampaignDuplicateController::class, 'duplicate'])->name('duplicate');
    Route::get('/{uid}/export/metrics', [CampaignExportController::class, 'exportMetrics'])->name('export.metrics');
    Route::get('/{uid}/export/logs', [CampaignExportController::class, 'exportLogs'])->name('export.logs');
    Route::get('/{uid}/top-links', [CampaignExportController::class, 'topLinks'])->name('top-links');
    Route::post('/{uid}/test', [CampaignTestController::class, 'sendTest'])->name('test');
    Route::get('/{uid}/deliverability', [CampaignTestController::class, 'deliverability'])->name('deliverability');
    Route::get('/{uid}/stats', [CampaignStatsController::class, 'stats'])->name('stats');
    Route::get('/{uid}/progress', [CampaignController::class, 'progress'])->name('progress');
    Route::get('/{uid}/backup', [CampaignBackupController::class, 'export'])->name('backup.export');
    Route::post('/backup', [CampaignBackupController::class, 'import'])->name('backup.import');

    Route::get('/{uid}/geo/opens', [GeoReportController::class, 'opensByCountry'])->name('geo.opens');
    Route::get('/{uid}/geo/clicks', [GeoReportController::class, 'clicksByCountry'])->name('geo.clicks');
    Route::get('/{uid}/cohorts', [CohortReportController::class, 'index'])->name('cohorts');

    Route::post('/{uid}/schedule', [CampaignController::class, 'schedule'])->name('schedule');
    Route::delete('/{uid}/schedule', [CampaignController::class, 'unschedule'])->name('unschedule');
    Route::post('/{uid}/submit-for-approval', [CampaignController::class, 'submitForApproval'])->name('submit-for-approval');
    Route::post('/{uid}/approve', [CampaignController::class, 'approve'])->name('approve');
    Route::post('/{uid}/reject', [CampaignController::class, 'reject'])->name('reject');
    Route::get('/trashed', [CampaignController::class, 'trashed'])->name('trashed');
    Route::post('/{uid}/restore', [CampaignController::class, 'restore'])->name('restore');
    Route::delete('/{uid}/force', [CampaignController::class, 'forceDelete'])->name('force-delete');
});

// AUTOMATIONS
Route::group(['prefix' => 'automations', 'as' => 'automations.'], function (): void {
    Route::get('/', [AutomationController::class, 'index'])->name('index');
    Route::post('/', [AutomationController::class, 'store'])->name('store');
    Route::get('/{uid}', [AutomationController::class, 'show'])->name('show');
    Route::put('/{uid}', [AutomationController::class, 'update'])->name('update');
    Route::delete('/{uid}', [AutomationController::class, 'delete'])->name('delete');

    Route::post('/{uid}/execute', [AutomationController::class, 'execute'])->name('execute');
    Route::post('/{uid}/enable', [AutomationController::class, 'enable'])->name('enable');
    Route::post('/{uid}/disable', [AutomationController::class, 'disable'])->name('disable');
});

// AUTOMATION RULES
Route::group(['prefix' => 'automation-rules', 'as' => 'automation-rules.'], function (): void {
    Route::get('/', [AutomationRuleController::class, 'index'])->name('index');
    Route::post('/', [AutomationRuleController::class, 'store'])->name('store');
    Route::get('/{uid}', [AutomationRuleController::class, 'show'])->name('show');
    Route::put('/{uid}', [AutomationRuleController::class, 'update'])->name('update');
    Route::delete('/{uid}', [AutomationRuleController::class, 'delete'])->name('delete');
    Route::post('/{uid}/toggle', [AutomationRuleController::class, 'toggle'])->name('toggle');
});

// SUBSCRIBERS
Route::group(['prefix' => 'subscribers', 'as' => 'subscribers.'], function (): void {
    Route::get('/', [SubscriberController::class, 'index'])->name('index');
    Route::post('/', [SubscriberController::class, 'store'])->name('store');
    Route::get('/{uid}', [SubscriberController::class, 'show'])->name('show');
    Route::put('/{uid}', [SubscriberController::class, 'update'])->name('update');
    Route::delete('/{uid}', [SubscriberController::class, 'delete'])->name('delete');
    Route::get('/{uid}/logs', [SubscriberController::class, 'logs'])->name('logs');
    Route::get('/{uid}/timeline', [SubscriberTimelineController::class, 'timeline'])->name('timeline');
    Route::get('/search', [SubscriberSearchController::class, 'search'])->name('search');
});

// MAILLISTS
Route::group(['prefix' => 'maillists', 'as' => 'maillists.'], function (): void {
    Route::get('/', [MaillistController::class, 'index'])->name('index');
    Route::post('/', [MaillistController::class, 'store'])->name('store');
    Route::get('/{uid}', [MaillistController::class, 'show'])->name('show');
    Route::put('/{uid}', [MaillistController::class, 'update'])->name('update');
    Route::delete('/{uid}', [MaillistController::class, 'delete'])->name('delete');

    Route::get('/{uid}/subscribers', [MaillistController::class, 'subscribers'])->name('subscribers');
    Route::post('/{uid}/verify', [MaillistController::class, 'verify'])->name('verify');
    Route::get('/{uid}/stats', [MaillistController::class, 'stats'])->name('stats');
    Route::get('/{uid}/export', [SubscriberExportController::class, 'export'])->name('export');

    Route::get('/{uid}/fields', [FieldController::class, 'index'])->name('fields.index');
    Route::post('/{uid}/fields', [FieldController::class, 'store'])->name('fields.store');
    Route::get('/{uid}/fields/{fieldUid}', [FieldController::class, 'show'])->name('fields.show');
    Route::put('/{uid}/fields/{fieldUid}', [FieldController::class, 'update'])->name('fields.update');
    Route::delete('/{uid}/fields/{fieldUid}', [FieldController::class, 'delete'])->name('fields.delete');
});

// TEMPLATES
Route::group(['prefix' => 'templates', 'as' => 'templates.'], function (): void {
    Route::get('/', [TemplateController::class, 'index'])->name('index');
    Route::post('/', [TemplateController::class, 'store'])->name('store');
    Route::get('/{uid}', [TemplateController::class, 'show'])->name('show');
    Route::put('/{uid}', [TemplateController::class, 'update'])->name('update');
    Route::delete('/{uid}', [TemplateController::class, 'delete'])->name('delete');
    Route::post('/{uid}/copy', [TemplateController::class, 'copy'])->name('copy');
});

// WEBHOOKS
Route::group(['prefix' => 'webhooks', 'as' => 'webhooks.'], function (): void {
    Route::get('/', [WebhookController::class, 'index'])->name('index');
    Route::post('/', [WebhookController::class, 'store'])->name('store');
    Route::get('/{uid}', [WebhookController::class, 'show'])->name('show');
    Route::put('/{uid}', [WebhookController::class, 'update'])->name('update');
    Route::delete('/{uid}', [WebhookController::class, 'delete'])->name('delete');
    Route::post('/{uid}/toggle', [WebhookController::class, 'toggle'])->name('toggle');
    Route::post('/{uid}/test', [WebhookController::class, 'test'])->name('test');
    Route::get('/{uid}/logs', [WebhookLogController::class, 'index'])->name('logs.index');
    Route::get('/{uid}/logs/stats', [WebhookLogController::class, 'stats'])->name('logs.stats');
});

// SENDER DOMAINS
Route::group(['prefix' => 'sender-domains', 'as' => 'sender-domains.'], function (): void {
    Route::get('/', [SenderDomainController::class, 'index'])->name('index');
    Route::post('/', [SenderDomainController::class, 'store'])->name('store');
    Route::get('/{uid}', [SenderDomainController::class, 'show'])->name('show');
    Route::post('/{uid}/verify', [SenderDomainController::class, 'verify'])->name('verify');
    Route::delete('/{uid}', [SenderDomainController::class, 'delete'])->name('delete');
});

// SEGMENTS
Route::group(['prefix' => 'segments', 'as' => 'segments.'], function (): void {
    Route::get('/', [SegmentController::class, 'index'])->name('index');
    Route::post('/', [SegmentController::class, 'store'])->name('store');
    Route::get('/{uid}', [SegmentController::class, 'show'])->name('show');
    Route::put('/{uid}', [SegmentController::class, 'update'])->name('update');
    Route::delete('/{uid}', [SegmentController::class, 'delete'])->name('delete');
    Route::get('/{uid}/preview', [SegmentController::class, 'preview'])->name('preview');
    Route::get('/{uid}/subscribers', [SegmentController::class, 'subscribers'])->name('subscribers');
});

// SENDING SERVERS
Route::group(['prefix' => 'sending-servers', 'as' => 'sending-servers.'], function (): void {
    Route::post('/{uid}/test', [SendingServerTestController::class, 'test'])->name('test');
});

// SUPPRESSION LISTS
Route::group(['prefix' => 'suppression-lists', 'as' => 'suppression-lists.'], function (): void {
    Route::get('/', [SuppressionListController::class, 'index'])->name('index');
    Route::post('/', [SuppressionListController::class, 'store'])->name('store');
    Route::post('/check', [SuppressionListController::class, 'check'])->name('check');
    Route::get('/{uid}', [SuppressionListController::class, 'show'])->name('show');
    Route::put('/{uid}', [SuppressionListController::class, 'update'])->name('update');
    Route::delete('/{uid}', [SuppressionListController::class, 'delete'])->name('delete');
});

// DUPLICATE SUBSCRIBERS
Route::group(['prefix' => 'subscribers/duplicates', 'as' => 'subscribers.duplicates.'], function (): void {
    Route::get('/', [DuplicateSubscriberController::class, 'index'])->name('index');
    Route::get('/{email}', [DuplicateSubscriberController::class, 'show'])->name('show');
    Route::post('/merge', [DuplicateSubscriberController::class, 'merge'])->name('merge');
});

// IMPORT DRY-RUN
Route::post('/subscribers/import/dry-run', [ImportDryRunController::class, 'dryRun'])
    ->name('subscribers.import.dry-run');

// BULK OPERATIONS
Route::post('/subscribers/bulk', [SubscriberBulkController::class, 'bulk'])
    ->name('subscribers.bulk');

// BLACKLIST
Route::group(['prefix' => 'blacklist', 'as' => 'blacklist.'], function (): void {
    Route::get('/', [BlacklistController::class, 'index'])->name('index');
    Route::post('/', [BlacklistController::class, 'store'])->name('store');
    Route::get('/{id}', [BlacklistController::class, 'show'])->name('show');
    Route::put('/{id}', [BlacklistController::class, 'update'])->name('update');
    Route::delete('/{id}', [BlacklistController::class, 'delete'])->name('delete');
});
