<?php

use Illuminate\Support\Facades\Route;
use Modules\Reviews\Http\Controllers\PublicReviewController;
use Modules\Reviews\Http\Controllers\ReportController;
use Modules\Reviews\Http\Controllers\ReviewAutoReplyRuleController;
use Modules\Reviews\Http\Controllers\ReviewBadgeController;
use Modules\Reviews\Http\Controllers\ReviewCompetitorController;
use Modules\Reviews\Http\Controllers\ReviewController;
use Modules\Reviews\Http\Controllers\ReviewDashboardController;
use Modules\Reviews\Http\Controllers\ReviewExportHistoryController;
use Modules\Reviews\Http\Controllers\ReviewGdprController;
use Modules\Reviews\Http\Controllers\ReviewHealthController;
use Modules\Reviews\Http\Controllers\ReviewLocationComparisonController;
use Modules\Reviews\Http\Controllers\ReviewReplyController;
use Modules\Reviews\Http\Controllers\ReviewReportController;
use Modules\Reviews\Http\Controllers\ReviewRequestCampaignController;
use Modules\Reviews\Http\Controllers\ReviewSavedFilterController;
use Modules\Reviews\Http\Controllers\ReviewSyncStatusController;
use Modules\Reviews\Http\Controllers\ReviewTemplateController;
use Modules\Reviews\Http\Controllers\ReviewWebhookSubscriptionController;
use Modules\Reviews\Http\Controllers\ReviewWidgetController;
use Modules\Reviews\Http\Controllers\Settings\AiSettingsController;
use Modules\Reviews\Http\Controllers\Settings\GoogleConnectionController;
use Modules\Reviews\Http\Controllers\Settings\GoogleLocationController;
use Modules\Reviews\Http\Controllers\Settings\NotificationPreferenceController;
use Modules\Reviews\Http\Controllers\Settings\ReviewImportController;
use Modules\Reviews\Http\Controllers\Settings\ReviewSettingsController;

// Rutas públicas - sin autenticación
Route::middleware(['web', 'throttle:60,1'])->group(function () {
    Route::get('/reviews/widget', [PublicReviewController::class, 'widget'])
        ->name('reviews.widget');

    Route::get('/reviews/embed-code', [PublicReviewController::class, 'embedCode'])
        ->name('reviews.embed-code');
});

Route::middleware(['web', 'auth'])->group(function () {
    Route::prefix('panel/settings/reviews')->name('settings.reviews.')->group(function () {
        // OAuth callback (must be before resource routes)
        Route::get('oauth/callback', [GoogleConnectionController::class, 'callback'])->name('oauth.callback');

        // Specific connection routes (must be before resource routes)
        Route::post('connections/bulk-action', [GoogleConnectionController::class, 'bulkAction'])->name('connections.bulk-action');
        Route::delete('connections/bulk-revoke', [GoogleConnectionController::class, 'bulkRevoke'])->name('connections.bulk-revoke');
        Route::delete('connections/{connection}/revoke', [GoogleConnectionController::class, 'destroy'])->name('connections.revoke');
        Route::post('connections/{connection}/reconnect', [GoogleConnectionController::class, 'reconnect'])->name('connections.reconnect');

        // Resource routes (must be after specific routes)
        Route::resource('connections', GoogleConnectionController::class);

        // Static location routes must come before resource (avoids {location} shadowing)
        Route::post('locations/bulk-action', [GoogleLocationController::class, 'bulkAction'])->name('locations.bulk-action');
        Route::post('locations/bulk-sync', [GoogleLocationController::class, 'bulkSync'])->name('locations.bulk-sync');
        Route::post('locations/sync-all', [GoogleLocationController::class, 'syncAll'])->name('locations.sync-all');
        Route::resource('locations', GoogleLocationController::class)->only(['index', 'create', 'store', 'update']);
        Route::post('locations/{location}/sync', [GoogleLocationController::class, 'sync'])->name('locations.sync');
        Route::get('locations/{location}/tags', [GoogleLocationController::class, 'tags'])->name('locations.tags.index');
        Route::post('locations/{location}/tags', [GoogleLocationController::class, 'storeTag'])->name('locations.tags.store');
        Route::delete('locations/{location}/tags/{slug}', [GoogleLocationController::class, 'destroyTag'])->name('locations.tags.destroy');

        Route::get('locations/{location}/import', [ReviewImportController::class, 'create'])->name('locations.import.create');
        Route::post('locations/{location}/import/csv', [ReviewImportController::class, 'storeCsv'])->name('locations.import.csv');
        Route::post('locations/{location}/import/manual', [ReviewImportController::class, 'storeManual'])->name('locations.import.manual');

        Route::get('config', [ReviewSettingsController::class, 'index'])->name('config.index');
        Route::match(['PUT', 'PATCH', 'POST'], 'config', [ReviewSettingsController::class, 'update'])->name('config.update');

        Route::get('widget', [ReviewSettingsController::class, 'widget'])->name('widget.index');

        Route::get('ai', [AiSettingsController::class, 'index'])->name('ai.index');
        Route::post('ai', [AiSettingsController::class, 'update'])->name('ai.update');
        Route::post('ai/test', [AiSettingsController::class, 'test'])->name('ai.test');

        Route::get('notifications', [NotificationPreferenceController::class, 'index'])->name('notifications.index');
        Route::post('notifications/update', [NotificationPreferenceController::class, 'update'])->name('notifications.update');
        Route::post('notifications/test/{type}', [NotificationPreferenceController::class, 'test'])->name('notifications.test');

        // Templates routes (in settings namespace)
        Route::get('templates', [ReviewTemplateController::class, 'index'])->name('templates.index');
        Route::post('templates', [ReviewTemplateController::class, 'store'])->name('templates.store');
        Route::get('templates/create', [ReviewTemplateController::class, 'create'])->name('templates.create');
        Route::post('templates/bulk-action', [ReviewTemplateController::class, 'bulkAction'])->name('templates.bulk-action');
        Route::delete('templates/bulk-delete', [ReviewTemplateController::class, 'bulkDelete'])->name('templates.bulk-delete');
        Route::get('templates/{template}', [ReviewTemplateController::class, 'show'])->name('templates.show');
        Route::get('templates/{template}/edit', [ReviewTemplateController::class, 'edit'])->name('templates.edit');
        Route::put('templates/{template}', [ReviewTemplateController::class, 'update'])->name('templates.update');
        Route::patch('templates/{template}/toggle-active', [ReviewTemplateController::class, 'toggleActive'])->name('templates.toggle-active');
        Route::delete('templates/{template}', [ReviewTemplateController::class, 'destroy'])->name('templates.destroy');
        Route::get('templates/{template}/versions', [ReviewTemplateController::class, 'versions'])->name('templates.versions');
    });

    Route::prefix('panel/reviews')->name('reviews.')->group(function () {
        Route::get('/', [ReviewDashboardController::class, 'index'])->name('dashboard');
        Route::get('dashboard/data', [ReviewDashboardController::class, 'data'])->name('dashboard.data');
        Route::get('list', [ReviewController::class, 'index'])->name('index');
        Route::get('data', [ReviewController::class, 'data'])->name('data');
        Route::get('tags/list', [ReviewController::class, 'tagsList'])->name('tags.list');
        Route::get('export', [ReviewController::class, 'export'])->name('export');
        Route::get('export/download/{file}', [ReviewController::class, 'downloadExport'])->name('export.download');
        Route::post('bulk-moderate', [ReviewController::class, 'bulkModerate'])->name('bulk-moderate');

        Route::resource('replies', ReviewReplyController::class)->only(['store', 'update', 'destroy']);
        Route::get('replies/scheduled', [ReviewReplyController::class, 'scheduled'])->name('replies.scheduled');
        Route::delete('replies/bulk-cancel', [ReviewReplyController::class, 'bulkCancel'])->name('replies.bulk-cancel');
        Route::post('replies/{reply}/publish', [ReviewReplyController::class, 'publish'])->name('replies.publish');
        Route::post('replies/{reply}/schedule', [ReviewReplyController::class, 'schedule'])->name('replies.schedule');
        Route::post('replies/bulk-approve', [ReviewController::class, 'bulkApproveReplies'])->name('replies.bulk-approve');
        Route::post('replies/bulk-publish', [ReviewController::class, 'bulkPublishReplies'])->name('replies.bulk-publish');

        // Saved filters routes (must be before {review} route)
        Route::post('saved-filters/{saved_filter}/apply', [ReviewSavedFilterController::class, 'apply'])->name('saved-filters.apply');
        Route::post('saved-filters/{saved_filter}/set-default', [ReviewSavedFilterController::class, 'setDefault'])->name('saved-filters.set-default');
        Route::post('saved-filters/{savedFilter}/share', [ReviewSavedFilterController::class, 'share'])->name('saved-filters.share');
        Route::delete('saved-filters/bulk-delete', [ReviewSavedFilterController::class, 'bulkDelete'])->name('saved-filters.bulk-delete');
        Route::resource('saved-filters', ReviewSavedFilterController::class)->except(['create', 'edit']);

        // Reports routes (must be before {review} route)
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::post('reports/generate', [ReportController::class, 'generate'])->name('reports.generate');
        Route::get('reports/list', [ReportController::class, 'list'])->name('reports.list');
        Route::get('reports/{generatedReport}/download', [ReportController::class, 'download'])->name('reports.download');
        Route::delete('reports/{generatedReport}', [ReportController::class, 'destroy'])->name('reports.destroy');

        Route::get('health', [ReviewHealthController::class, 'check'])->name('health');
        Route::get('sync-status', [ReviewSyncStatusController::class, 'index'])->name('sync-status.index');
        Route::post('sync-status/{connection}/sync-now', [ReviewSyncStatusController::class, 'syncNow'])->name('sync-status.sync-now');

        Route::get('exports/history', [ReviewExportHistoryController::class, 'index'])->name('exports.history');
        Route::get('exports/history/{history}/download', [ReviewExportHistoryController::class, 'download'])->name('exports.history.download');
        Route::delete('exports/history/bulk-delete', [ReviewExportHistoryController::class, 'bulkDelete'])->name('exports.history.bulk-delete');
        Route::delete('exports/history/{exportHistory}', [ReviewExportHistoryController::class, 'destroy'])->name('exports.history.destroy');

        Route::get('dashboard/kpis', [ReviewDashboardController::class, 'kpisData'])->name('dashboard.kpis');
        Route::get('templates/{template}/preview', [ReviewDashboardController::class, 'templatePreview'])->name('templates.preview');

        Route::post('gdpr/export', [ReviewGdprController::class, 'export'])->name('gdpr.export');
        Route::delete('gdpr/data', [ReviewGdprController::class, 'delete'])->name('gdpr.delete');

        Route::get('analytics/data', [ReviewController::class, 'analyticsData'])->name('analytics.data');

        // Approval workflow routes (must be before {review} catch-all routes)
        Route::get('replies/pending-approvals', [ReviewController::class, 'pendingApprovals'])->name('replies.pending-approvals');
        Route::post('replies/{reply}/request-approval', [ReviewController::class, 'requestReplyApproval'])->name('replies.request-approval');
        Route::post('replies/{reply}/approve', [ReviewController::class, 'approveReply'])->name('replies.approve');
        Route::post('replies/{reply}/reject', [ReviewController::class, 'rejectReply'])->name('replies.reject');

        Route::post('autoreply/bulk-action', [ReviewAutoReplyRuleController::class, 'bulkAction'])->name('autoreply.bulk-action');
        Route::resource('autoreply', ReviewAutoReplyRuleController::class)
            ->parameters(['autoreply' => 'autoReplyRule']);
        Route::post('autoreply/{autoReplyRule}/toggle', [ReviewAutoReplyRuleController::class, 'toggle'])->name('autoreply.toggle');

        // Campaign routes
        Route::resource('campaigns', ReviewRequestCampaignController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
        Route::post('campaigns/{campaign}/send', [ReviewRequestCampaignController::class, 'sendToList'])->name('campaigns.send');
        Route::get('campaigns/{campaign}/qr', [ReviewRequestCampaignController::class, 'qrCode'])->name('campaigns.qr');
        Route::get('campaigns/{campaign}/stats', [ReviewRequestCampaignController::class, 'stats'])->name('campaigns.stats');
        Route::post('campaigns/{campaign}/toggle', [ReviewRequestCampaignController::class, 'toggle'])->name('campaigns.toggle');
        Route::post('campaigns/bulk-destroy', [ReviewRequestCampaignController::class, 'bulkDestroy'])->name('campaigns.bulk-destroy');
    });
});

Route::middleware(['web', 'auth'])->prefix('panel/reviews')->name('reviews.')->group(function () {
    Route::patch('replies/{reply}/inline', [ReviewController::class, 'updateReplyInline'])->name('replies.update-inline');
    Route::post('reviews/{review}/report', [ReviewController::class, 'reportReview'])->name('report');
});

Route::middleware(['web', 'auth'])->prefix('panel/settings/reviews')->name('settings.reviews.')->group(function () {
    Route::post('templates/{template}/translations', [ReviewTemplateController::class, 'addTranslation'])->name('templates.translations.store');
    Route::delete('templates/{template}/translations/{language}', [ReviewTemplateController::class, 'removeTranslation'])->name('templates.translations.destroy');
});

// Widget tokens (auth required)
Route::middleware(['web', 'auth'])->prefix('panel/reviews')->group(function () {
    Route::get('widgets', [ReviewWidgetController::class, 'index'])->name('reviews.widgets.index');
    Route::post('widgets', [ReviewWidgetController::class, 'store'])->name('reviews.widgets.store');
    Route::delete('widgets/{widgetToken}', [ReviewWidgetController::class, 'destroy'])->name('reviews.widgets.destroy');
    Route::get('widgets/{widgetToken}/preview', [ReviewWidgetController::class, 'widgetPreview'])->name('reviews.widgets.preview');

    // Webhook subscriptions (auth required)
    Route::resource('webhook', ReviewWebhookSubscriptionController::class)
        ->only(['index', 'create', 'store', 'edit', 'update', 'destroy'])
        ->names('reviews.webhook-subscriptions');
    Route::post('webhook/bulk-action', [ReviewWebhookSubscriptionController::class, 'bulkAction'])->name('reviews.webhook-subscriptions.bulk-action');
    Route::patch('webhook/{webhook}/toggle', [ReviewWebhookSubscriptionController::class, 'toggle'])->name('reviews.webhook-subscriptions.toggle');
});

Route::middleware(['web', 'auth'])->prefix('panel/reviews')->name('reviews.')->group(function () {
    Route::get('reports/monthly', [ReviewReportController::class, 'downloadMonthly'])->name('reports.monthly');
});

Route::middleware(['web', 'auth'])->prefix('panel/reviews')->name('reviews.')->group(function () {
    Route::get('locations/comparison', [ReviewLocationComparisonController::class, 'index'])->name('locations.comparison');
    Route::get('locations/comparison/data', [ReviewLocationComparisonController::class, 'data'])->name('locations.comparison.data');
    Route::get('locations/comparison/rankings', [ReviewLocationComparisonController::class, 'rankings'])->name('locations.comparison.rankings');
    Route::get('analytics/response-time', [ReviewLocationComparisonController::class, 'responseTimeData'])->name('analytics.response-time');
});

// Badge generator routes (auth required)
Route::middleware(['web', 'auth'])->prefix('panel/reviews')->name('reviews.')->group(function () {
    Route::get('badges', [ReviewBadgeController::class, 'index'])->name('badges.index');
    Route::get('badges/{location}/preview', [ReviewBadgeController::class, 'preview'])->name('badges.preview');
    Route::get('badges/{location}/download', [ReviewBadgeController::class, 'download'])->name('badges.download');
    Route::get('badges/{location}/embed', [ReviewBadgeController::class, 'embedCode'])->name('badges.embed');
});

// Competitor tracking routes
Route::middleware(['web', 'auth'])->prefix('panel/reviews')->name('reviews.')->group(function () {
    Route::get('competitors', [ReviewCompetitorController::class, 'index'])->name('competitors.index');
    Route::get('competitors/create', [ReviewCompetitorController::class, 'create'])->name('competitors.create');
    Route::get('competitors/compare', [ReviewCompetitorController::class, 'comparePage'])->name('competitors.compare');
    Route::get('competitors/comparison', [ReviewCompetitorController::class, 'comparison'])->name('competitors.comparison');
    Route::post('competitors', [ReviewCompetitorController::class, 'store'])->name('competitors.store');
    Route::delete('competitors/bulk-destroy', [ReviewCompetitorController::class, 'bulkDestroy'])->name('competitors.bulk-destroy');
    Route::get('competitors/{competitor}/edit', [ReviewCompetitorController::class, 'edit'])->name('competitors.edit');
    Route::patch('competitors/{competitor}', [ReviewCompetitorController::class, 'update'])->name('competitors.update');
    Route::delete('competitors/{competitor}', [ReviewCompetitorController::class, 'destroy'])->name('competitors.destroy');
});

// Velocity analytics route
Route::middleware(['web', 'auth'])->prefix('panel/reviews')->name('reviews.')->group(function () {
    Route::get('analytics/velocity', [ReviewController::class, 'velocityData'])->name('analytics.velocity');
});

// Review translation routes (auth required)
Route::middleware(['web', 'auth'])->prefix('panel/reviews')->name('reviews.')->group(function () {
    Route::post('{review}/translate', [ReviewController::class, 'translate'])->name('translate');
    Route::patch('{review}/translations/{locale}', [ReviewController::class, 'updateTranslation'])->name('translations.update');
});

// {review} wildcard routes — must be LAST to avoid shadowing specific routes
Route::middleware(['web', 'auth'])->prefix('panel/reviews')->name('reviews.')->group(function () {
    Route::post('{review}/quarantine', [ReviewController::class, 'quarantine'])->name('quarantine');
    Route::post('{review}/ai-reply', [ReviewController::class, 'generateAiReply'])->name('ai-reply');
    Route::get('{review}/suggestions', [ReviewController::class, 'suggestions'])->name('suggestions');
    Route::patch('{review}/moderate', [ReviewController::class, 'moderate'])->name('moderate');
    Route::get('{review}', [ReviewController::class, 'show'])->name('show');
});
