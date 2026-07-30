<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskSocial\Http\Controllers\Managers\SocialSettingsController;
use Modules\HelpdeskSocial\Http\Controllers\Webhooks\MetaWebhookController;

// Webhooks (public, no auth, rate limited)
Route::prefix('webhooks/helpdesk/social')->middleware('throttle:helpdesk-webhook-inbound')->group(function () {
    Route::get('meta', [MetaWebhookController::class, 'verify'])->name('helpdesksocial.webhooks.meta.verify');
    Route::post('meta', [MetaWebhookController::class, 'handle'])->name('helpdesksocial.webhooks.meta.handle');
});

// Admin panel routes
Route::prefix('panel/helpdesk/social')->middleware(['web', 'auth'])->group(function () {
    // Accounts
    Route::get('accounts', [SocialSettingsController::class, 'accounts'])->name('helpdesksocial.accounts.index');
    Route::get('accounts/create', [SocialSettingsController::class, 'createAccount'])->name('helpdesksocial.accounts.create');
    Route::post('accounts', [SocialSettingsController::class, 'storeAccount'])->name('helpdesksocial.accounts.store');
    Route::get('accounts/{account}/edit', [SocialSettingsController::class, 'editAccount'])->name('helpdesksocial.accounts.edit');
    Route::put('accounts/{account}', [SocialSettingsController::class, 'updateAccount'])->name('helpdesksocial.accounts.update');
    Route::delete('accounts/{account}', [SocialSettingsController::class, 'destroyAccount'])->name('helpdesksocial.accounts.destroy');

    // Inbox
    Route::get('inbox', [SocialSettingsController::class, 'inbox'])->name('helpdesksocial.inbox.index');
    Route::get('inbox/{comment}', [SocialSettingsController::class, 'showComment'])->name('helpdesksocial.inbox.show');
    Route::post('inbox/{comment}/reply', [SocialSettingsController::class, 'replyComment'])->name('helpdesksocial.inbox.reply');
    Route::post('inbox/{comment}/assign', [SocialSettingsController::class, 'assignComment'])->name('helpdesksocial.inbox.assign');
    Route::post('inbox/{comment}/spam', [SocialSettingsController::class, 'markCommentAsSpam'])->name('helpdesksocial.inbox.spam');
    Route::post('inbox/{comment}/escalate', [SocialSettingsController::class, 'escalateComment'])->name('helpdesksocial.inbox.escalate');

    // Rules
    Route::get('rules', [SocialSettingsController::class, 'rules'])->name('helpdesksocial.rules.index');
    Route::get('rules/create', [SocialSettingsController::class, 'createRule'])->name('helpdesksocial.rules.create');
    Route::post('rules', [SocialSettingsController::class, 'storeRule'])->name('helpdesksocial.rules.store');
    Route::get('rules/{rule}/edit', [SocialSettingsController::class, 'editRule'])->name('helpdesksocial.rules.edit');
    Route::put('rules/{rule}', [SocialSettingsController::class, 'updateRule'])->name('helpdesksocial.rules.update');
    Route::delete('rules/{rule}', [SocialSettingsController::class, 'destroyRule'])->name('helpdesksocial.rules.destroy');

    // Templates
    Route::get('templates', [SocialSettingsController::class, 'templates'])->name('helpdesksocial.templates.index');
    Route::get('templates/create', [SocialSettingsController::class, 'createTemplate'])->name('helpdesksocial.templates.create');
    Route::post('templates', [SocialSettingsController::class, 'storeTemplate'])->name('helpdesksocial.templates.store');
    Route::get('templates/{template}/edit', [SocialSettingsController::class, 'editTemplate'])->name('helpdesksocial.templates.edit');
    Route::put('templates/{template}', [SocialSettingsController::class, 'updateTemplate'])->name('helpdesksocial.templates.update');
    Route::delete('templates/{template}', [SocialSettingsController::class, 'destroyTemplate'])->name('helpdesksocial.templates.destroy');

    // Analytics
    Route::get('analytics', [SocialSettingsController::class, 'analytics'])->name('helpdesksocial.analytics.index');
    Route::get('analytics/agents', [SocialSettingsController::class, 'agentPerformance'])->name('helpdesksocial.analytics.agents');

    // Tags
    Route::get('tags', [SocialSettingsController::class, 'tags'])->name('helpdesksocial.tags.index');
    Route::post('tags', [SocialSettingsController::class, 'storeTag'])->name('helpdesksocial.tags.store');
    Route::put('tags/{tag}', [SocialSettingsController::class, 'updateTag'])->name('helpdesksocial.tags.update');
    Route::delete('tags/{tag}', [SocialSettingsController::class, 'destroyTag'])->name('helpdesksocial.tags.destroy');

    // SLA Policies
    Route::get('sla-policies', [SocialSettingsController::class, 'slaPolicies'])->name('helpdesksocial.sla-policies.index');
    Route::post('sla-policies', [SocialSettingsController::class, 'storeSlaPolicy'])->name('helpdesksocial.sla-policies.store');
    Route::put('sla-policies/{policy}', [SocialSettingsController::class, 'updateSlaPolicy'])->name('helpdesksocial.sla-policies.update');
    Route::delete('sla-policies/{policy}', [SocialSettingsController::class, 'destroySlaPolicy'])->name('helpdesksocial.sla-policies.destroy');

    // Conversations
    Route::get('conversations', [SocialSettingsController::class, 'conversations'])->name('helpdesksocial.conversations.index');

    // Assignment Rules
    Route::get('assignment-rules', [SocialSettingsController::class, 'assignmentRules'])->name('helpdesksocial.assignment-rules.index');
    Route::post('assignment-rules', [SocialSettingsController::class, 'storeAssignmentRule'])->name('helpdesksocial.assignment-rules.store');
    Route::put('assignment-rules/{rule}', [SocialSettingsController::class, 'updateAssignmentRule'])->name('helpdesksocial.assignment-rules.update');
    Route::delete('assignment-rules/{rule}', [SocialSettingsController::class, 'destroyAssignmentRule'])->name('helpdesksocial.assignment-rules.destroy');

    // Mentions
    Route::get('mentions', [SocialSettingsController::class, 'mentions'])->name('helpdesksocial.mentions.index');

    // Approval Requests
    Route::get('approval-requests', [SocialSettingsController::class, 'approvalRequests'])->name('helpdesksocial.approval-requests.index');

    // Competitors
    Route::get('competitors', [SocialSettingsController::class, 'competitors'])->name('helpdesksocial.competitors.index');
    Route::post('competitors', [SocialSettingsController::class, 'storeCompetitor'])->name('helpdesksocial.competitors.store');
    Route::put('competitors/{competitor}', [SocialSettingsController::class, 'updateCompetitor'])->name('helpdesksocial.competitors.update');
    Route::delete('competitors/{competitor}', [SocialSettingsController::class, 'destroyCompetitor'])->name('helpdesksocial.competitors.destroy');

    // Saved Replies (AJAX)
    Route::get('saved-replies', [SocialSettingsController::class, 'savedReplies'])->name('helpdesksocial.saved-replies');
});
