<?php

use Illuminate\Support\Facades\Route;
use Modules\Helpdesk\Http\Controllers\Api\TagsAutocompleteController;
use Modules\Helpdesk\Http\Controllers\Managers\AgentPerformanceController;
use Modules\Helpdesk\Http\Controllers\Managers\AgentsController;
use Modules\Helpdesk\Http\Controllers\Managers\AiController;
use Modules\Helpdesk\Http\Controllers\Managers\BulkConversationsController;
use Modules\Helpdesk\Http\Controllers\Managers\CannedRepliesController;
use Modules\Helpdesk\Http\Controllers\Managers\Compliance\GdprController;
use Modules\Helpdesk\Http\Controllers\Managers\Compliance\TwoFactorController;
use Modules\Helpdesk\Http\Controllers\Managers\ConversationItemsController;
use Modules\Helpdesk\Http\Controllers\Managers\ConversationMessagesController;
use Modules\Helpdesk\Http\Controllers\Managers\ConversationsController as HelpdeskConversationsController;
use Modules\Helpdesk\Http\Controllers\Managers\CustomerInsightsController;
use Modules\Helpdesk\Http\Controllers\Managers\CustomersController as HelpdeskCustomersController;
use Modules\Helpdesk\Http\Controllers\Managers\DashboardController;
use Modules\Helpdesk\Http\Controllers\Managers\GlobalSearchController;
use Modules\Helpdesk\Http\Controllers\Managers\HeatmapReportController;
use Modules\Helpdesk\Http\Controllers\Managers\HelpCenterController;
use Modules\Helpdesk\Http\Controllers\Managers\LeaderboardController;
use Modules\Helpdesk\Http\Controllers\Managers\LiveDashboardController;
use Modules\Helpdesk\Http\Controllers\Managers\LiveVisitorsController;
use Modules\Helpdesk\Http\Controllers\Managers\ReportsController;
use Modules\Helpdesk\Http\Controllers\Managers\TranslateController;
use Modules\Helpdesk\Http\Controllers\Managers\TrendsReportController;

Route::group(['prefix' => ''], function () {

    // Translation endpoint
    Route::post('/translate', TranslateController::class)
        ->middleware('throttle:30,1')
        ->name('manager.helpdesk.translate');

    // Tags autocomplete (web, throttled, authenticated)
    Route::get('/api/tags-autocomplete', TagsAutocompleteController::class)
        ->middleware('throttle:60,1')
        ->name('manager.helpdesk.api.tags.autocomplete');

    // Canned replies search (slash-menu in inbox composer)
    Route::get('/canned-replies/search', [CannedRepliesController::class, 'search'])
        ->middleware('throttle:60,1')
        ->name('manager.helpdesk.canned-replies.search');

    // Customer search (JSON, throttled)
    Route::get('/customers/search', [HelpdeskCustomersController::class, 'search'])
        ->middleware('throttle:60,1')
        ->name('manager.helpdesk.customers.search');

    // Global search (JSON, throttled)
    Route::get('/search/global', GlobalSearchController::class)
        ->middleware('throttle:60,1')
        ->name('manager.helpdesk.search.global');

    // Main Helpdesk Index
    Route::get('/', [DashboardController::class, 'index'])->name('manager.helpdesk');

    // Live dashboard
    Route::get('/dashboard/live', [LiveDashboardController::class, 'index'])->name('manager.helpdesk.dashboard.live');
    Route::get('/dashboard/live/metrics', [LiveDashboardController::class, 'metrics'])->middleware('throttle:30,1')->name('manager.helpdesk.dashboard.live.metrics');

    // Customers
    Route::get('/customers', [HelpdeskCustomersController::class, 'index'])->name('manager.helpdesk.customers.index');
    Route::get('/customers/create', [HelpdeskCustomersController::class, 'create'])->name('manager.helpdesk.customers.create');
    Route::post('/customers', [HelpdeskCustomersController::class, 'store'])->name('manager.helpdesk.customers.store');
    Route::get('/customers/{customer}', [HelpdeskCustomersController::class, 'show'])->name('manager.helpdesk.customers.show');
    Route::get('/customers/{customer}/edit', [HelpdeskCustomersController::class, 'edit'])->name('manager.helpdesk.customers.edit');
    Route::put('/customers/{customer}', [HelpdeskCustomersController::class, 'update'])->name('manager.helpdesk.customers.update');
    Route::delete('/customers/{customer}', [HelpdeskCustomersController::class, 'destroy'])->name('manager.helpdesk.customers.destroy');
    Route::post('/customers/{customer}/restore', [HelpdeskCustomersController::class, 'restore'])->name('manager.helpdesk.customers.restore');
    Route::delete('/customers/{customer}/force-delete', [HelpdeskCustomersController::class, 'forceDelete'])->name('manager.helpdesk.customers.forceDelete');
    Route::post('/customers/{customer}/ban', [HelpdeskCustomersController::class, 'ban'])->name('manager.helpdesk.customers.ban');
    Route::post('/customers/{customer}/unban', [HelpdeskCustomersController::class, 'unban'])->name('manager.helpdesk.customers.unban');
    Route::get('/customers/{customer}/conversations', [HelpdeskCustomersController::class, 'conversations'])->name('manager.helpdesk.customers.conversations')->middleware('throttle:30,1');
    Route::get('/customers/{customer}/media', [HelpdeskCustomersController::class, 'media'])->name('manager.helpdesk.customers.media')->middleware(['throttle:60,1', 'can:helpdesk.conversations.view']);

    // Conversations bulk
    Route::post('/conversations/bulk', [BulkConversationsController::class, 'handle'])->name('manager.helpdesk.conversations.bulk');

    // Conversations
    Route::get('/conversations/list', [HelpdeskConversationsController::class, 'listJson'])->middleware('throttle:120,1')->name('manager.helpdesk.conversations.list');
    Route::get('/conversations/kanban', [HelpdeskConversationsController::class, 'kanban'])->name('manager.helpdesk.conversations.kanban');
    Route::get('/conversations', [HelpdeskConversationsController::class, 'index'])->name('manager.helpdesk.conversations.index');
    Route::get('/conversations/create', [HelpdeskConversationsController::class, 'create'])->name('manager.helpdesk.conversations.create');
    Route::post('/conversations', [HelpdeskConversationsController::class, 'store'])->name('manager.helpdesk.conversations.store');
    Route::get('/conversations/{conversation}', [HelpdeskConversationsController::class, 'show'])->name('manager.helpdesk.conversations.show');
    Route::get('/conversations/{conversation}/edit', [HelpdeskConversationsController::class, 'edit'])->name('manager.helpdesk.conversations.edit');
    Route::put('/conversations/{conversation}', [HelpdeskConversationsController::class, 'update'])->name('manager.helpdesk.conversations.update');
    Route::delete('/conversations/{conversation}', [HelpdeskConversationsController::class, 'destroy'])->name('manager.helpdesk.conversations.destroy');
    Route::post('/conversations/{conversation}/restore', [HelpdeskConversationsController::class, 'restore'])->name('manager.helpdesk.conversations.restore');
    Route::delete('/conversations/{conversation}/force-delete', [HelpdeskConversationsController::class, 'forceDelete'])->name('manager.helpdesk.conversations.forceDelete');
    Route::post('/conversations/{conversation}/close', [HelpdeskConversationsController::class, 'close'])->name('manager.helpdesk.conversations.close');
    Route::post('/conversations/{conversation}/reopen', [HelpdeskConversationsController::class, 'reopen'])->name('manager.helpdesk.conversations.reopen');
    Route::post('/conversations/{conversation}/archive', [HelpdeskConversationsController::class, 'archive'])->name('manager.helpdesk.conversations.archive');
    Route::post('/conversations/{conversation}/unarchive', [HelpdeskConversationsController::class, 'unarchive'])->name('manager.helpdesk.conversations.unarchive');
    Route::post('/conversations/{conversation}/messages', [HelpdeskConversationsController::class, 'storeMessage'])->name('manager.helpdesk.conversations.messages.store');
    Route::post('/conversations/{conversation}/mark-read', [ConversationMessagesController::class, 'markConversationRead'])->name('manager.helpdesk.conversations.mark-read');
    Route::post('/conversations/{conversation}/typing', [ConversationMessagesController::class, 'broadcastTyping'])->name('manager.helpdesk.conversations.typing');

    // AI assistant endpoints (sugerir respuesta + traducir mensaje)
    Route::post('/conversations/{conversation}/ai/suggest-replies', [AiController::class, 'suggestReplies'])
        ->middleware('throttle:30,1')
        ->name('manager.helpdesk.conversations.ai.suggest-replies');
    Route::post('/conversations/items/{item}/translate', [AiController::class, 'translateItem'])
        ->middleware('throttle:60,1')
        ->name('manager.helpdesk.conversations.items.translate');
    Route::post('/conversations/{conversation}/attachments', [HelpdeskConversationsController::class, 'uploadAttachments'])->name('manager.helpdesk.conversations.attachments.store');
    Route::post('/conversations/{conversation}/attachments/forward', [HelpdeskConversationsController::class, 'forwardAttachment'])->name('manager.helpdesk.conversations.attachments.forward');
    Route::post('/conversations/{conversation}/contact', [HelpdeskConversationsController::class, 'storeContact'])->name('manager.helpdesk.conversations.contact.store');
    Route::post('/conversations/{conversation}/location', [HelpdeskConversationsController::class, 'storeLocation'])->name('manager.helpdesk.conversations.location.store');
    Route::post('/conversations/{conversation}/send-email', [HelpdeskConversationsController::class, 'sendEmail'])
        ->middleware('throttle:30,1')
        ->name('manager.helpdesk.conversations.send-email');
    Route::post('/conversations/{conversation}/send-hsm', [HelpdeskConversationsController::class, 'sendHsm'])
        ->middleware('throttle:30,1')
        ->name('manager.helpdesk.conversations.send-hsm');
    Route::get('/conversations/{conversation}/merge-candidates', [HelpdeskConversationsController::class, 'mergeCandidates'])->name('manager.helpdesk.conversations.merge-candidates')->middleware('throttle:30,1');
    Route::post('/conversations/{conversation}/merge', [HelpdeskConversationsController::class, 'merge'])->name('manager.helpdesk.conversations.merge')->middleware('throttle:10,1');
    Route::post('/conversations/{conversation}/snooze', [HelpdeskConversationsController::class, 'snooze'])->name('manager.helpdesk.conversations.snooze');
    Route::post('/conversations/{conversation}/pin', [HelpdeskConversationsController::class, 'togglePin'])->name('manager.helpdesk.conversations.pin');
    Route::post('/conversations/{conversation}/mute', [HelpdeskConversationsController::class, 'toggleMute'])->name('manager.helpdesk.conversations.mute');
    Route::post('/conversations/{conversation}/block-contact', [HelpdeskConversationsController::class, 'blockContact'])->name('manager.helpdesk.conversations.block-contact');
    Route::post('/conversations/{conversation}/mark-spam', [HelpdeskConversationsController::class, 'markSpam'])->name('manager.helpdesk.conversations.mark-spam');
    Route::post('/conversations/{conversation}/ai-suggestions', [HelpdeskConversationsController::class, 'aiSuggestions'])->name('manager.helpdesk.conversations.ai-suggestions')->middleware('throttle:30,1');
    Route::put('/conversations/{conversation}/draft', [HelpdeskConversationsController::class, 'saveDraft'])->name('manager.helpdesk.conversations.draft.save');
    Route::post('/conversations/{conversation}/messages/scheduled', [HelpdeskConversationsController::class, 'storeScheduledMessage'])->name('manager.helpdesk.conversations.messages.scheduled');

    // AI features
    Route::post('/conversations/{conversation}/ai/suggest-replies', [AiController::class, 'suggestReplies'])
        ->name('manager.helpdesk.conversations.ai.suggest-replies')
        ->middleware('throttle:20,1');
    Route::post('/conversation-items/{item}/translate', [AiController::class, 'translateItem'])
        ->name('manager.helpdesk.conversation-items.translate')
        ->middleware('throttle:30,1');

    // Conversation items
    Route::post('/conversation-items/{item}/react', [ConversationItemsController::class, 'react'])
        ->name('manager.helpdesk.conversation-items.react')
        ->middleware('throttle:30,1');

    // Agents management
    Route::prefix('agents')->name('agents.')->group(function () {
        Route::get('/', [AgentsController::class, 'index'])->name('index');
        Route::get('{agent}', [AgentsController::class, 'show'])->name('show');
        Route::get('{agent}/edit', [AgentsController::class, 'edit'])->name('edit');
        Route::put('{agent}', [AgentsController::class, 'update'])->name('update');
    });

    // Reports
    Route::prefix('reports')->name('manager.helpdesk.reports.')->group(function () {
        Route::get('/', [ReportsController::class, 'index'])->name('index');
        Route::get('/export', [ReportsController::class, 'export'])->name('export')->middleware('throttle:helpdesk-export');
        Route::get('/agents', [ReportsController::class, 'agentPerformance'])->name('agents');
        Route::get('/trend', [ReportsController::class, 'trend'])->name('trend');
    });

    // Help Center Manager
    Route::prefix('helpcenter')->group(function () {
        Route::get('/', [HelpCenterController::class, 'index'])->name('manager.helpdesk.helpcenter.index');

        // Categories
        Route::get('/categories', [HelpCenterController::class, 'index'])->name('manager.helpdesk.helpcenter.categories');
        Route::get('/categories/create', [HelpCenterController::class, 'create'])->name('manager.helpdesk.helpcenter.categories.create');
        Route::post('/categories/store', [HelpCenterController::class, 'store'])->name('manager.helpdesk.helpcenter.categories.store');
        Route::get('/categories/{id}', [HelpCenterController::class, 'showCategory'])->name('manager.helpdesk.helpcenter.categories.show');
        Route::get('/categories/edit/{id}', [HelpCenterController::class, 'edit'])->name('manager.helpdesk.helpcenter.categories.edit');
        Route::post('/categories/update', [HelpCenterController::class, 'update'])->name('manager.helpdesk.helpcenter.categories.update');
        Route::get('/categories/destroy/{id}', [HelpCenterController::class, 'destroy'])->name('manager.helpdesk.helpcenter.categories.destroy');

        // Sections
        Route::get('/sections/create', [HelpCenterController::class, 'createSection'])->name('manager.helpdesk.helpcenter.sections.create');
        Route::post('/sections/store', [HelpCenterController::class, 'storeSection'])->name('manager.helpdesk.helpcenter.sections.store');
        Route::get('/sections/{id}', [HelpCenterController::class, 'showSection'])->name('manager.helpdesk.helpcenter.sections.show');
        Route::get('/sections/{id}/edit', [HelpCenterController::class, 'editSection'])->name('manager.helpdesk.helpcenter.sections.edit');
        Route::post('/sections/update', [HelpCenterController::class, 'updateSection'])->name('manager.helpdesk.helpcenter.sections.update');
        Route::get('/sections/{id}/destroy', [HelpCenterController::class, 'destroySection'])->name('manager.helpdesk.helpcenter.sections.destroy');
        Route::get('/sections/{id}/articles/create', [HelpCenterController::class, 'createArticleInSection'])->name('manager.helpdesk.helpcenter.sections.articles.create');

        // Articles
        Route::get('/articles', [HelpCenterController::class, 'articlesIndex'])->name('manager.helpdesk.helpcenter.articles');
        Route::get('/articles/create', [HelpCenterController::class, 'createArticle'])->name('manager.helpdesk.helpcenter.articles.create');
        Route::post('/articles/store', [HelpCenterController::class, 'storeArticle'])->name('manager.helpdesk.helpcenter.articles.store');
        Route::get('/articles/edit/{id}', [HelpCenterController::class, 'editArticle'])->name('manager.helpdesk.helpcenter.articles.edit');
        Route::post('/articles/update', [HelpCenterController::class, 'updateArticle'])->name('manager.helpdesk.helpcenter.articles.update');
        Route::get('/articles/destroy/{id}', [HelpCenterController::class, 'destroyArticle'])->name('manager.helpdesk.helpcenter.articles.destroy');
    });

    // Reports avanzados (Fase 5)
    Route::prefix('reports')->name('manager.helpdesk.reports.')->group(function () {
        Route::get('heatmap', [HeatmapReportController::class, 'index'])->name('heatmap');
        Route::get('agents', [AgentPerformanceController::class, 'index'])->name('agents');
        Route::get('trends', [TrendsReportController::class, 'index'])->name('trends');
        Route::get('trends/data', [TrendsReportController::class, 'data'])->name('trends.data');
    });

    // Customer 360 endpoint
    Route::get('/customers/{customer}/insights', [CustomerInsightsController::class, 'show'])
        ->name('manager.helpdesk.customers.insights');

    // Settings routes moved to routes/settings.php (Patrón A: panel/settings/helpdesk/*)

    // Live Visitors
    Route::prefix('live-visitors')->name('manager.helpdesk.live-visitors.')->group(function () {
        Route::get('/', [LiveVisitorsController::class, 'index'])->name('index');
        Route::get('data', [LiveVisitorsController::class, 'data'])->name('data')->middleware('throttle:30,1');
    });

    // Team Leaderboard
    Route::get('team/leaderboard', [LeaderboardController::class, 'index'])->name('manager.helpdesk.team.leaderboard');

    // GDPR panel
    Route::get('gdpr', [GdprController::class, 'panel'])->name('manager.helpdesk.gdpr.panel');

    // GDPR per-customer actions
    Route::prefix('customers/{customer}/gdpr')->name('manager.helpdesk.customers.gdpr.')->group(function () {
        Route::get('export', [GdprController::class, 'export'])->name('export');
        Route::post('delete', [GdprController::class, 'delete'])->name('delete');
    });

    // 2FA
    Route::prefix('2fa')->name('manager.helpdesk.2fa.')->group(function () {
        Route::get('setup', [TwoFactorController::class, 'setup'])->name('setup');
        Route::get('challenge', [TwoFactorController::class, 'challenge'])->name('challenge');
        Route::post('enable', [TwoFactorController::class, 'enable'])->name('enable');
        Route::post('confirm', [TwoFactorController::class, 'confirm'])->name('confirm');
        Route::post('verify', [TwoFactorController::class, 'verify'])->name('verify');
        Route::post('disable', [TwoFactorController::class, 'disable'])->name('disable');
    });
});
