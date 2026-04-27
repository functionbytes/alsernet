<?php

use Illuminate\Support\Facades\Route;
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
use Modules\Reviews\Http\Controllers\ReviewWebhookSubscriptionController;
use Modules\Reviews\Http\Controllers\ReviewWidgetController;

Route::middleware(['web', 'auth'])->prefix('panel/reviews')->name('reviews.')->group(function () {

    // Dashboard
    Route::get('/', [ReviewDashboardController::class, 'index'])->name('dashboard');
    Route::get('dashboard/data', [ReviewDashboardController::class, 'data'])->name('dashboard.data');
    Route::get('dashboard/kpis', [ReviewDashboardController::class, 'kpisData'])->name('dashboard.kpis');
    Route::get('templates/{template}/preview', [ReviewDashboardController::class, 'templatePreview'])->name('templates.preview');

    // Review list & exports
    Route::get('list', [ReviewController::class, 'index'])->name('index');
    Route::get('data', [ReviewController::class, 'data'])->name('data');
    Route::get('tags/list', [ReviewController::class, 'tagsList'])->name('tags.list');
    Route::get('export', [ReviewController::class, 'export'])->name('export');
    Route::get('export/download/{file}', [ReviewController::class, 'downloadExport'])->name('export.download');
    Route::post('bulk-moderate', [ReviewController::class, 'bulkModerate'])->name('bulk-moderate');
    Route::get('analytics/data', [ReviewController::class, 'analyticsData'])->name('analytics.data');
    Route::get('analytics/velocity', [ReviewController::class, 'velocityData'])->name('analytics.velocity');

    // Replies
    Route::resource('replies', ReviewReplyController::class)->only(['store', 'update', 'destroy']);
    Route::get('replies/scheduled', [ReviewReplyController::class, 'scheduled'])->name('replies.scheduled');
    Route::delete('replies/bulk-cancel', [ReviewReplyController::class, 'bulkCancel'])->name('replies.bulk-cancel');
    Route::post('replies/{reply}/publish', [ReviewReplyController::class, 'publish'])->name('replies.publish');
    Route::post('replies/{reply}/schedule', [ReviewReplyController::class, 'schedule'])->name('replies.schedule');
    Route::post('replies/bulk-approve', [ReviewController::class, 'bulkApproveReplies'])->name('replies.bulk-approve');
    Route::post('replies/bulk-publish', [ReviewController::class, 'bulkPublishReplies'])->name('replies.bulk-publish');
    Route::patch('replies/{reply}/inline', [ReviewController::class, 'updateReplyInline'])->name('replies.update-inline');

    // Approval workflow
    Route::get('replies/pending-approvals', [ReviewController::class, 'pendingApprovals'])->name('replies.pending-approvals');
    Route::post('replies/{reply}/request-approval', [ReviewController::class, 'requestReplyApproval'])->name('replies.request-approval');
    Route::post('replies/{reply}/approve', [ReviewController::class, 'approveReply'])->name('replies.approve');
    Route::post('replies/{reply}/reject', [ReviewController::class, 'rejectReply'])->name('replies.reject');

    // Saved filters (must be before {review} wildcard)
    Route::post('saved-filters/{saved_filter}/apply', [ReviewSavedFilterController::class, 'apply'])->name('saved-filters.apply');
    Route::post('saved-filters/{saved_filter}/set-default', [ReviewSavedFilterController::class, 'setDefault'])->name('saved-filters.set-default');
    Route::post('saved-filters/{savedFilter}/share', [ReviewSavedFilterController::class, 'share'])->name('saved-filters.share');
    Route::delete('saved-filters/bulk-delete', [ReviewSavedFilterController::class, 'bulkDelete'])->name('saved-filters.bulk-delete');
    Route::resource('saved-filters', ReviewSavedFilterController::class)->except(['create', 'edit']);

    // Reports
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::post('reports/generate', [ReportController::class, 'generate'])->name('reports.generate');
    Route::get('reports/list', [ReportController::class, 'list'])->name('reports.list');
    Route::get('reports/{generatedReport}/download', [ReportController::class, 'download'])->name('reports.download');
    Route::delete('reports/{generatedReport}', [ReportController::class, 'destroy'])->name('reports.destroy');
    Route::get('reports/monthly', [ReviewReportController::class, 'downloadMonthly'])->name('reports.monthly');

    // Health & sync
    Route::get('health', [ReviewHealthController::class, 'check'])->name('health');
    Route::get('sync-status', [ReviewSyncStatusController::class, 'index'])->name('sync-status.index');
    Route::post('sync-status/{connection}/sync-now', [ReviewSyncStatusController::class, 'syncNow'])->name('sync-status.sync-now');

    // Export history
    Route::get('exports/history', [ReviewExportHistoryController::class, 'index'])->name('exports.history');
    Route::get('exports/history/{history}/download', [ReviewExportHistoryController::class, 'download'])->name('exports.history.download');
    Route::delete('exports/history/bulk-delete', [ReviewExportHistoryController::class, 'bulkDelete'])->name('exports.history.bulk-delete');
    Route::delete('exports/history/{exportHistory}', [ReviewExportHistoryController::class, 'destroy'])->name('exports.history.destroy');

    // GDPR
    Route::post('gdpr/export', [ReviewGdprController::class, 'export'])->name('gdpr.export');
    Route::delete('gdpr/data', [ReviewGdprController::class, 'delete'])->name('gdpr.delete');

    // Auto-reply rules
    Route::post('autoreply/bulk-action', [ReviewAutoReplyRuleController::class, 'bulkAction'])->name('autoreply.bulk-action');
    Route::resource('autoreply', ReviewAutoReplyRuleController::class)
        ->parameters(['autoreply' => 'autoReplyRule']);
    Route::post('autoreply/{autoReplyRule}/toggle', [ReviewAutoReplyRuleController::class, 'toggle'])->name('autoreply.toggle');

    // Campaigns
    Route::resource('campaigns', ReviewRequestCampaignController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    Route::post('campaigns/{campaign}/send', [ReviewRequestCampaignController::class, 'sendToList'])->name('campaigns.send');
    Route::get('campaigns/{campaign}/qr', [ReviewRequestCampaignController::class, 'qrCode'])->name('campaigns.qr');
    Route::get('campaigns/{campaign}/stats', [ReviewRequestCampaignController::class, 'stats'])->name('campaigns.stats');
    Route::post('campaigns/{campaign}/toggle', [ReviewRequestCampaignController::class, 'toggle'])->name('campaigns.toggle');
    Route::post('campaigns/bulk-destroy', [ReviewRequestCampaignController::class, 'bulkDestroy'])->name('campaigns.bulk-destroy');

    // Widgets
    Route::get('widgets', [ReviewWidgetController::class, 'index'])->name('widgets.index');
    Route::post('widgets', [ReviewWidgetController::class, 'store'])->name('widgets.store');
    Route::delete('widgets/{widgetToken}', [ReviewWidgetController::class, 'destroy'])->name('widgets.destroy');
    Route::get('widgets/{widgetToken}/preview', [ReviewWidgetController::class, 'widgetPreview'])->name('widgets.preview');

    // Webhook subscriptions
    Route::resource('webhook', ReviewWebhookSubscriptionController::class)
        ->only(['index', 'create', 'store', 'edit', 'update', 'destroy'])
        ->names('reviews.webhook-subscriptions');
    Route::post('webhook/bulk-action', [ReviewWebhookSubscriptionController::class, 'bulkAction'])->name('webhook-subscriptions.bulk-action');
    Route::patch('webhook/{webhook}/toggle', [ReviewWebhookSubscriptionController::class, 'toggle'])->name('webhook-subscriptions.toggle');

    // Badges
    Route::get('badges', [ReviewBadgeController::class, 'index'])->name('badges.index');
    Route::get('badges/{location}/preview', [ReviewBadgeController::class, 'preview'])->name('badges.preview');
    Route::get('badges/{location}/download', [ReviewBadgeController::class, 'download'])->name('badges.download');
    Route::get('badges/{location}/embed', [ReviewBadgeController::class, 'embedCode'])->name('badges.embed');

    // Competitors
    Route::get('competitors', [ReviewCompetitorController::class, 'index'])->name('competitors.index');
    Route::get('competitors/create', [ReviewCompetitorController::class, 'create'])->name('competitors.create');
    Route::get('competitors/compare', [ReviewCompetitorController::class, 'comparePage'])->name('competitors.compare');
    Route::get('competitors/comparison', [ReviewCompetitorController::class, 'comparison'])->name('competitors.comparison');
    Route::post('competitors', [ReviewCompetitorController::class, 'store'])->name('competitors.store');
    Route::delete('competitors/bulk-destroy', [ReviewCompetitorController::class, 'bulkDestroy'])->name('competitors.bulk-destroy');
    Route::get('competitors/{competitor}/edit', [ReviewCompetitorController::class, 'edit'])->name('competitors.edit');
    Route::patch('competitors/{competitor}', [ReviewCompetitorController::class, 'update'])->name('competitors.update');
    Route::delete('competitors/{competitor}', [ReviewCompetitorController::class, 'destroy'])->name('competitors.destroy');

    // Location comparison & analytics
    Route::get('locations/comparison', [ReviewLocationComparisonController::class, 'index'])->name('locations.comparison');
    Route::get('locations/comparison/data', [ReviewLocationComparisonController::class, 'data'])->name('locations.comparison.data');
    Route::get('locations/comparison/rankings', [ReviewLocationComparisonController::class, 'rankings'])->name('locations.comparison.rankings');
    Route::get('analytics/response-time', [ReviewLocationComparisonController::class, 'responseTimeData'])->name('analytics.response-time');

    // Translations (must be before {review} wildcard)
    Route::post('{review}/translate', [ReviewController::class, 'translate'])->name('translate');
    Route::patch('{review}/translations/{locale}', [ReviewController::class, 'updateTranslation'])->name('translations.update');

    // Review report
    Route::post('reviews/{review}/report', [ReviewController::class, 'reportReview'])->name('report');

    // {review} wildcard routes — must be LAST to avoid shadowing specific routes
    Route::post('{review}/quarantine', [ReviewController::class, 'quarantine'])->name('quarantine');
    Route::post('{review}/ai-reply', [ReviewController::class, 'generateAiReply'])->name('ai-reply');
    Route::get('{review}/suggestions', [ReviewController::class, 'suggestions'])->name('suggestions');
    Route::patch('{review}/moderate', [ReviewController::class, 'moderate'])->name('moderate');
    Route::get('{review}', [ReviewController::class, 'show'])->name('show');
});
