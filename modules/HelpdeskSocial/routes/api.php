<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskSocial\Http\Controllers\Api\SocialAccountsController;
use Modules\HelpdeskSocial\Http\Controllers\Api\SocialAnalyticsController;
use Modules\HelpdeskSocial\Http\Controllers\Api\SocialApprovalRequestsController;
use Modules\HelpdeskSocial\Http\Controllers\Api\SocialAssignmentRulesController;
use Modules\HelpdeskSocial\Http\Controllers\Api\SocialCompetitorsController;
use Modules\HelpdeskSocial\Http\Controllers\Api\SocialConversationsController;
use Modules\HelpdeskSocial\Http\Controllers\Api\SocialInboxController;
use Modules\HelpdeskSocial\Http\Controllers\Api\SocialMentionsController;
use Modules\HelpdeskSocial\Http\Controllers\Api\SocialNotesController;
use Modules\HelpdeskSocial\Http\Controllers\Api\SocialRulesController;
use Modules\HelpdeskSocial\Http\Controllers\Api\SocialSlaPoliciesController;
use Modules\HelpdeskSocial\Http\Controllers\Api\SocialTagsController;
use Modules\HelpdeskSocial\Http\Controllers\Api\SocialTemplatesController;

Route::prefix('helpdesk/social')->middleware(['auth:sanctum', 'log.social.api', 'throttle:helpdesk-social-api'])->group(function () {

    // Accounts
    Route::apiResource('accounts', SocialAccountsController::class);
    Route::post('accounts/{account}/toggle', [SocialAccountsController::class, 'toggleActive'])->name('helpdesksocial.accounts.toggle');

    // Inbox
    Route::get('inbox', [SocialInboxController::class, 'index'])->name('helpdesksocial.inbox.index');
    Route::get('inbox/stats', [SocialInboxController::class, 'stats'])->name('helpdesksocial.inbox.stats');
    Route::get('inbox/{comment}', [SocialInboxController::class, 'show'])->name('helpdesksocial.inbox.show');
    Route::post('inbox/{comment}/reply', [SocialInboxController::class, 'reply'])->name('helpdesksocial.inbox.reply');
    Route::post('inbox/{comment}/spam', [SocialInboxController::class, 'markAsSpam'])->name('helpdesksocial.inbox.spam');
    Route::post('inbox/{comment}/escalate', [SocialInboxController::class, 'markAsEscalated'])->name('helpdesksocial.inbox.escalate');
    Route::post('inbox/{comment}/assign', [SocialInboxController::class, 'assign'])->name('helpdesksocial.inbox.assign');
    Route::post('inbox/bulk', [SocialInboxController::class, 'bulk'])->name('helpdesksocial.inbox.bulk');

    // Tags
    Route::apiResource('tags', SocialTagsController::class);

    // Notes
    Route::get('comments/{comment}/notes', [SocialNotesController::class, 'index'])->name('helpdesksocial.notes.index');
    Route::post('comments/{comment}/notes', [SocialNotesController::class, 'store'])->name('helpdesksocial.notes.store');
    Route::delete('notes/{note}', [SocialNotesController::class, 'destroy'])->name('helpdesksocial.notes.destroy');

    // SLA Policies
    Route::apiResource('sla-policies', SocialSlaPoliciesController::class)->parameters(['sla-policies' => 'policy']);

    // Conversations
    Route::apiResource('conversations', SocialConversationsController::class)->only(['index', 'show', 'update', 'destroy']);

    // Assignment Rules
    Route::apiResource('assignment-rules', SocialAssignmentRulesController::class)->parameters(['assignment-rules' => 'rule']);
    Route::post('assignment-rules/{rule}/toggle', [SocialAssignmentRulesController::class, 'toggleActive'])->name('helpdesksocial.assignment-rules.toggle');

    // Mentions
    Route::apiResource('mentions', SocialMentionsController::class)->only(['index', 'show', 'update', 'destroy']);

    // Approval Requests
    Route::get('approval-requests', [SocialApprovalRequestsController::class, 'index'])->name('helpdesksocial.approval-requests.index');
    Route::post('approval-requests', [SocialApprovalRequestsController::class, 'store'])->name('helpdesksocial.approval-requests.store');
    Route::get('approval-requests/{approvalRequest}', [SocialApprovalRequestsController::class, 'show'])->name('helpdesksocial.approval-requests.show');
    Route::post('approval-requests/{approvalRequest}/approve', [SocialApprovalRequestsController::class, 'approve'])->name('helpdesksocial.approval-requests.approve');
    Route::post('approval-requests/{approvalRequest}/reject', [SocialApprovalRequestsController::class, 'reject'])->name('helpdesksocial.approval-requests.reject');

    // Competitors
    Route::apiResource('competitors', SocialCompetitorsController::class);

    // Rules
    Route::apiResource('rules', SocialRulesController::class);
    Route::post('rules/{rule}/toggle', [SocialRulesController::class, 'toggleActive'])->name('helpdesksocial.rules.toggle');
    Route::post('rules/simulate', [SocialRulesController::class, 'simulate'])->name('helpdesksocial.rules.simulate');

    // Templates
    Route::apiResource('templates', SocialTemplatesController::class);
    Route::post('templates/{template}/preview', [SocialTemplatesController::class, 'preview'])->name('helpdesksocial.templates.preview');

    // Analytics
    Route::get('analytics/overview', [SocialAnalyticsController::class, 'overview'])->name('helpdesksocial.analytics.overview');
    Route::get('analytics/metrics', [SocialAnalyticsController::class, 'metrics'])->name('helpdesksocial.analytics.metrics');
    Route::get('analytics/agents', [SocialAnalyticsController::class, 'agentsPerformance'])->name('helpdesksocial.analytics.agents');
    Route::get('analytics/agent-performance', [SocialAnalyticsController::class, 'agentPerformance'])->name('helpdesksocial.analytics.agent-performance');
    Route::get('analytics/sla-overview', [SocialAnalyticsController::class, 'slaOverview'])->name('helpdesksocial.analytics.sla-overview');
    Route::get('analytics/sentiment-breakdown', [SocialAnalyticsController::class, 'sentimentBreakdown'])->name('helpdesksocial.analytics.sentiment-breakdown');
    Route::get('analytics/competitor-comparison', [SocialAnalyticsController::class, 'competitorComparison'])->name('helpdesksocial.analytics.competitor-comparison');
});
