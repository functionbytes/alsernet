<?php

use Illuminate\Support\Facades\Route;
use Modules\Helpdesk\Http\Controllers\Managers\AgentPerformanceController;
use Modules\Helpdesk\Http\Controllers\Managers\AgentPresenceController;
use Modules\Helpdesk\Http\Controllers\Managers\AgentProfileController;
use Modules\Helpdesk\Http\Controllers\Managers\AgentsController;
use Modules\Helpdesk\Http\Controllers\Managers\AiSuggestionsController;
use Modules\Helpdesk\Http\Controllers\Managers\AtRiskCustomersReportController;
use Modules\Helpdesk\Http\Controllers\Managers\AutoAssignmentController;
use Modules\Helpdesk\Http\Controllers\Managers\BulkConversationsController;
use Modules\Helpdesk\Http\Controllers\Managers\CannedRepliesController;
use Modules\Helpdesk\Http\Controllers\Managers\Compliance\GdprController;
use Modules\Helpdesk\Http\Controllers\Managers\Compliance\TwoFactorController;
use Modules\Helpdesk\Http\Controllers\Managers\ConversationExportController;
use Modules\Helpdesk\Http\Controllers\Managers\ConversationItemsController;
use Modules\Helpdesk\Http\Controllers\Managers\ConversationMessagesController;
use Modules\Helpdesk\Http\Controllers\Managers\ConversationsController as HelpdeskConversationsController;
use Modules\Helpdesk\Http\Controllers\Managers\ConversationViewsController;
use Modules\Helpdesk\Http\Controllers\Managers\CsatReportController;
use Modules\Helpdesk\Http\Controllers\Managers\CustomerEcommerceController;
use Modules\Helpdesk\Http\Controllers\Managers\CustomerInsightsController;
use Modules\Helpdesk\Http\Controllers\Managers\CustomerProfileController;
use Modules\Helpdesk\Http\Controllers\Managers\CustomersController as HelpdeskCustomersController;
use Modules\Helpdesk\Http\Controllers\Managers\DashboardController;
use Modules\Helpdesk\Http\Controllers\Managers\ExportController;
use Modules\Helpdesk\Http\Controllers\Managers\GlobalSearchController;
use Modules\Helpdesk\Http\Controllers\Managers\HeatmapReportController;
use Modules\Helpdesk\Http\Controllers\Managers\HelpdeskSimulatorController;
use Modules\Helpdesk\Http\Controllers\Managers\LeaderboardController;
use Modules\Helpdesk\Http\Controllers\Managers\LiveDashboardController;
use Modules\Helpdesk\Http\Controllers\Managers\RemindersController;
use Modules\Helpdesk\Http\Controllers\Managers\SearchController;
use Modules\Helpdesk\Http\Controllers\Managers\SlaBreachesReportController;
use Modules\Helpdesk\Http\Controllers\Managers\SuggestedArticlesController;
use Modules\Helpdesk\Http\Controllers\Managers\TrendsReportController;
use Modules\Helpdesk\Http\Controllers\Managers\WebRtcAgentController;

Route::group(['prefix' => ''], function () {

    // Canned replies search (slash-menu in inbox composer)
    Route::get('/canned-replies/search', [CannedRepliesController::class, 'search'])
        ->middleware('throttle:60,1')
        ->name('manager.helpdesk.canned-replies.search');

    // Customer search (JSON, throttled)
    Route::get('/customers/search', [HelpdeskCustomersController::class, 'search'])
        ->middleware('throttle:60,1')
        ->name('manager.helpdesk.customers.search');

    // Customer merge
    Route::post('/customers/merge', [HelpdeskCustomersController::class, 'merge'])
        ->middleware(['can:helpdesk.customers.merge', 'throttle:10,1'])
        ->name('manager.helpdesk.customers.merge');

    // Global search (JSON autocomplete, throttled)
    Route::get('/search/global', GlobalSearchController::class)
        ->middleware('throttle:60,1')
        ->name('manager.helpdesk.search.global');

    // Agent presence state (heartbeat + manual state change)
    Route::prefix('presence')->name('manager.helpdesk.presence.')->group(function () {
        Route::post('heartbeat', [AgentPresenceController::class, 'heartbeat'])->name('heartbeat')->middleware('throttle:120,1');
        Route::post('state', [AgentPresenceController::class, 'setState'])->name('state')->middleware('throttle:60,1');
        Route::get('me', [AgentPresenceController::class, 'me'])->name('me')->middleware('throttle:60,1');
        Route::get('agents', [AgentPresenceController::class, 'list'])->name('agents')->middleware('throttle:30,1');
    });

    // Global search (full page with tabs)
    Route::get('/search', [SearchController::class, 'index'])
        ->name('manager.helpdesk.search');

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
    Route::post('/customers/{customer}/ban', [HelpdeskCustomersController::class, 'ban'])->name('manager.helpdesk.customers.ban');
    Route::post('/customers/{customer}/unban', [HelpdeskCustomersController::class, 'unban'])->name('manager.helpdesk.customers.unban');
    Route::get('/customers/{customer}/conversations', [HelpdeskCustomersController::class, 'conversations'])->name('manager.helpdesk.customers.conversations')->middleware('throttle:30,1');
    Route::get('/customers/{customer}/media', [HelpdeskCustomersController::class, 'media'])->name('manager.helpdesk.customers.media')->middleware(['throttle:60,1', 'can:helpdesk.conversations.view']);
    Route::get('/customers/{customer}/emails', [HelpdeskCustomersController::class, 'emails'])->name('manager.helpdesk.customers.emails');
    Route::get('/customers/{customer}/emails-data', [HelpdeskCustomersController::class, 'emailsData'])->name('manager.helpdesk.customers.emails-data');

    // Saved conversation views (inbox sidebar)
    Route::post('/views', [ConversationViewsController::class, 'store'])
        ->middleware('can:helpdesk.conversations.view')
        ->name('manager.helpdesk.views.store');
    Route::delete('/views/{view}', [ConversationViewsController::class, 'destroy'])
        ->middleware('can:helpdesk.conversations.view')
        ->name('manager.helpdesk.views.destroy');

    // Conversations bulk
    Route::post('/conversations/bulk', [BulkConversationsController::class, 'handle'])->name('manager.helpdesk.conversations.bulk');

    // Conversations
    Route::get('/conversations/list', [HelpdeskConversationsController::class, 'listJson'])->middleware('throttle:120,1')->name('manager.helpdesk.conversations.list');
    Route::get('/conversations/kanban', [HelpdeskConversationsController::class, 'kanban'])->name('manager.helpdesk.conversations.kanban');
    Route::get('/conversations', [HelpdeskConversationsController::class, 'index'])->name('manager.helpdesk.conversations.index');
    Route::get('/conversations/create', [HelpdeskConversationsController::class, 'create'])->name('manager.helpdesk.conversations.create');
    Route::get('/conversations/email-templates', [HelpdeskConversationsController::class, 'emailTemplates'])
        ->middleware('throttle:60,1')
        ->name('manager.helpdesk.conversations.email-templates');
    Route::post('/conversations', [HelpdeskConversationsController::class, 'store'])->name('manager.helpdesk.conversations.store');
    // Active macros list for the inbox bulk picker (?sort=used = most used first).
    // Declared before the {conversation} catch-all so "macros-picker" isn't swallowed.
    Route::get('/conversations/macros-picker', [HelpdeskConversationsController::class, 'macrosForPicker'])
        ->middleware('throttle:60,1')
        ->name('manager.helpdesk.conversations.macros-picker');
    Route::get('/conversations/{conversation}', [HelpdeskConversationsController::class, 'show'])->name('manager.helpdesk.conversations.show');
    Route::put('/conversations/{conversation}', [HelpdeskConversationsController::class, 'update'])->name('manager.helpdesk.conversations.update');
    Route::delete('/conversations/{conversation}', [HelpdeskConversationsController::class, 'destroy'])->name('manager.helpdesk.conversations.destroy');
    Route::post('/conversations/{conversation}/restore', [HelpdeskConversationsController::class, 'restore'])->name('manager.helpdesk.conversations.restore');
    Route::post('/conversations/{conversation}/close', [HelpdeskConversationsController::class, 'close'])->name('manager.helpdesk.conversations.close');
    Route::post('/conversations/{conversation}/reopen', [HelpdeskConversationsController::class, 'reopen'])->name('manager.helpdesk.conversations.reopen');
    Route::post('/conversations/{conversation}/archive', [HelpdeskConversationsController::class, 'archive'])->name('manager.helpdesk.conversations.archive');
    Route::post('/conversations/{conversation}/unarchive', [HelpdeskConversationsController::class, 'unarchive'])->name('manager.helpdesk.conversations.unarchive');
    Route::post('/conversations/{conversation}/messages', [HelpdeskConversationsController::class, 'storeMessage'])->name('manager.helpdesk.conversations.messages.store');
    Route::post('/conversations/{conversation}/sync-commerce', [HelpdeskSimulatorController::class, 'resyncConversation'])->middleware('throttle:30,1')->name('manager.helpdesk.conversations.sync-commerce');

    // Recordatorios personales del agente (#59 ve-reminder)
    Route::post('/reminders', [RemindersController::class, 'store'])->name('manager.helpdesk.reminders.store');
    Route::post('/conversations/{conversation}/reminders', [RemindersController::class, 'store'])->name('manager.helpdesk.conversations.reminders.store');

    // Exportar el transcript de UNA conversación (#57 ve-export) — distinto del
    // /exports/conversations (plural) que exporta la lista completa.
    Route::get('/exports/conversation-transcript', [ConversationExportController::class, 'export'])->middleware('throttle:20,1')->name('manager.helpdesk.exports.conversation-transcript');
    Route::post('/exports/conversation-transcript/email', [ConversationExportController::class, 'sendByEmail'])->middleware('throttle:10,1')->name('manager.helpdesk.exports.conversation-transcript.email');

    // Estrategia global de auto-asignación (#78 ve-auto-assign)
    Route::get('/auto-assignment', [AutoAssignmentController::class, 'show'])->name('manager.helpdesk.auto-assignment.show');
    Route::put('/auto-assignment', [AutoAssignmentController::class, 'update'])->name('manager.helpdesk.auto-assignment.update');

    // Banco de pruebas omnicanal (simulador de canales + contexto PrestaShop/gestion)
    Route::get('/simulator', [HelpdeskSimulatorController::class, 'index'])->name('manager.helpdesk.simulator.index');
    Route::get('/simulator/customers', [HelpdeskSimulatorController::class, 'searchCustomers'])->middleware('throttle:60,1')->name('manager.helpdesk.simulator.customers');
    Route::get('/simulator/orders', [HelpdeskSimulatorController::class, 'orders'])->middleware('throttle:60,1')->name('manager.helpdesk.simulator.orders');
    Route::post('/simulator/simulate', [HelpdeskSimulatorController::class, 'simulate'])->middleware('throttle:30,1')->name('manager.helpdesk.simulator.simulate');
    Route::post('/conversations/{conversation}/mark-read', [ConversationMessagesController::class, 'markConversationRead'])->name('manager.helpdesk.conversations.mark-read');
    Route::post('/conversations/{conversation}/typing', [ConversationMessagesController::class, 'broadcastTyping'])->name('manager.helpdesk.conversations.typing');

    // WebRTC signaling — agent side (answer + ICE candidates back to widget)
    Route::post('/conversations/{conversation}/webrtc/answer', [WebRtcAgentController::class, 'answer'])
        ->middleware('throttle:60,1')
        ->name('manager.helpdesk.conversations.webrtc.answer');
    Route::post('/conversations/{conversation}/webrtc/ice', [WebRtcAgentController::class, 'ice'])
        ->middleware('throttle:60,1')
        ->name('manager.helpdesk.conversations.webrtc.ice');
    Route::post('/conversations/{conversation}/webrtc/end', [WebRtcAgentController::class, 'end'])
        ->name('manager.helpdesk.conversations.webrtc.end');
    Route::post('/conversations/{conversation}/webrtc/request', [WebRtcAgentController::class, 'request'])
        ->middleware('throttle:10,1')
        ->name('manager.helpdesk.conversations.webrtc.request');
    Route::get('/conversations/{conversation}/livestream/history', [WebRtcAgentController::class, 'livestreamHistory'])
        ->middleware('throttle:30,1')
        ->name('manager.helpdesk.conversations.livestream.history');

    // AI reply suggestions for the inbox "ai-suggest" modal
    Route::post('/conversations/{conversation}/ai/suggestions', AiSuggestionsController::class)
        ->middleware(['can:helpdesk.conversations.update', 'throttle:30,1'])
        ->name('manager.helpdesk.conversations.ai.suggestions');

    // Knowledge/helpcenter article suggestions for the inbox composer panel
    Route::get('/conversations/{conversation}/suggested-articles', SuggestedArticlesController::class)
        ->middleware(['can:helpdesk.conversations.view,conversation', 'throttle:30,1'])
        ->name('manager.helpdesk.conversations.suggested-articles');

    Route::post('/conversations/{conversation}/attachments', [HelpdeskConversationsController::class, 'uploadAttachments'])->name('manager.helpdesk.conversations.attachments.store');
    Route::post('/conversations/{conversation}/attachments/forward', [HelpdeskConversationsController::class, 'forwardAttachment'])->name('manager.helpdesk.conversations.attachments.forward');
    Route::post('/conversations/{conversation}/contact', [HelpdeskConversationsController::class, 'storeContact'])->name('manager.helpdesk.conversations.contact.store');
    Route::post('/conversations/{conversation}/location', [HelpdeskConversationsController::class, 'storeLocation'])->name('manager.helpdesk.conversations.location.store');
    Route::get('/conversations/{conversation}/email-templates/preview', [HelpdeskConversationsController::class, 'previewEmailTemplate'])
        ->middleware('throttle:30,1')
        ->name('manager.helpdesk.conversations.email-templates.preview');
    Route::get('/conversations/{conversation}/preview', [HelpdeskConversationsController::class, 'previewJson'])
        ->middleware('throttle:120,1')
        ->name('manager.helpdesk.conversations.preview');
    Route::post('/conversations/{conversation}/send-email', [HelpdeskConversationsController::class, 'sendEmail'])
        ->middleware('throttle:30,1')
        ->name('manager.helpdesk.conversations.send-email');
    Route::get('/conversations/{conversation}/emails', [HelpdeskConversationsController::class, 'emailLogIndex'])
        ->middleware('throttle:60,1')
        ->name('manager.helpdesk.conversations.emails.index');
    Route::get('/conversations/{conversation}/emails/{emailLog}', [HelpdeskConversationsController::class, 'emailLogShow'])
        ->middleware('throttle:60,1')
        ->name('manager.helpdesk.conversations.emails.show');
    Route::get('/conversations/{conversation}/viewer-items', [HelpdeskConversationsController::class, 'conversationViewerItems'])
        ->middleware(['can:helpdesk.conversations.view,conversation', 'throttle:60,1'])
        ->name('manager.helpdesk.conversations.viewer-items');
    Route::get('/conversations/{conversation}/items', [HelpdeskConversationsController::class, 'olderItems'])
        ->middleware(['can:helpdesk.conversations.view,conversation', 'throttle:120,1'])
        ->name('manager.helpdesk.conversations.items.older');
    // SPA pane: thread + right-panel HTML for in-place conversation switching.
    Route::get('/conversations/{conversation}/pane', [HelpdeskConversationsController::class, 'pane'])
        ->middleware(['can:helpdesk.conversations.view,conversation', 'throttle:120,1'])
        ->name('manager.helpdesk.conversations.pane');
    Route::get('/conversations/{conversation}/audit-log', [HelpdeskConversationsController::class, 'auditLog'])
        ->middleware(['can:helpdesk.conversations.view,conversation', 'throttle:30,1'])
        ->name('manager.helpdesk.conversations.audit-log');
    Route::post('/conversations/{conversation}/send-hsm', [HelpdeskConversationsController::class, 'sendHsm'])
        ->middleware('throttle:60,1')
        ->name('manager.helpdesk.conversations.send-hsm');
    Route::get('/conversations/{conversation}/merge-candidates', [HelpdeskConversationsController::class, 'mergeCandidates'])->name('manager.helpdesk.conversations.merge-candidates')->middleware('throttle:30,1');
    Route::post('/conversations/{conversation}/merge', [HelpdeskConversationsController::class, 'merge'])->name('manager.helpdesk.conversations.merge')->middleware('throttle:10,1');
    Route::post('/conversations/{conversation}/link-customer', [HelpdeskConversationsController::class, 'linkCustomer'])
        ->middleware(['can:helpdesk.conversations.link-customer', 'throttle:10,1'])
        ->name('manager.helpdesk.conversations.link-customer');
    Route::post('/conversations/{conversation}/snooze', [HelpdeskConversationsController::class, 'snooze'])->name('manager.helpdesk.conversations.snooze');
    Route::post('/conversations/{conversation}/pin', [HelpdeskConversationsController::class, 'togglePin'])->name('manager.helpdesk.conversations.pin');
    Route::post('/conversations/{conversation}/mute', [HelpdeskConversationsController::class, 'toggleMute'])->name('manager.helpdesk.conversations.mute');
    Route::post('/conversations/{conversation}/block-contact', [HelpdeskConversationsController::class, 'blockContact'])->name('manager.helpdesk.conversations.block-contact');
    Route::post('/conversations/{conversation}/mark-spam', [HelpdeskConversationsController::class, 'markSpam'])->name('manager.helpdesk.conversations.mark-spam');
    Route::post('/conversations/{conversation}/messages/scheduled', [HelpdeskConversationsController::class, 'storeScheduledMessage'])->name('manager.helpdesk.conversations.messages.scheduled');
    Route::post('/conversations/{conversation}/send-csat', [HelpdeskConversationsController::class, 'sendCsatSurvey'])->name('manager.helpdesk.conversations.send-csat')->middleware('throttle:10,1');
    Route::post('/conversations/{conversation}/macros/{macro}', [HelpdeskConversationsController::class, 'applyMacro'])->name('manager.helpdesk.conversations.macros.apply');
    // Apply one macro to many selected conversations (inbox bulk action bar)
    Route::post('/conversations/bulk-macro', [HelpdeskConversationsController::class, 'bulkApplyMacro'])
        ->middleware('can:helpdesk.conversations.update')
        ->name('manager.helpdesk.conversations.bulk-macro');
    // /conversations/{c}/ticket and /conversations/{c}/ticket-detail/{t} are
    // owned by HelpdeskTickets (see modules/HelpdeskTickets/routes/managers.php)
    // so Helpdesk does not depend on HelpdeskTickets at the routing layer.
    Route::get('/conversations/{conversation}/notes', [HelpdeskConversationsController::class, 'internalNotes'])->name('manager.helpdesk.conversations.notes');
    Route::get('/hsm-templates', [HelpdeskConversationsController::class, 'hsmTemplates'])->name('manager.helpdesk.hsm-templates');

    // Bubble context-menu actions: react / forward / info — agent-only, light load.
    // Use named throttler so they don't share the global counter that the
    // notifications poller (which fires every 3s) is constantly burning.
    Route::post('/messages/{item}/react', [HelpdeskConversationsController::class, 'reactToMessage'])
        ->middleware('throttle:helpdesk-msg-actions')
        ->name('manager.helpdesk.messages.react');
    Route::post('/messages/{item}/forward', [HelpdeskConversationsController::class, 'forwardMessage'])
        ->middleware('throttle:helpdesk-msg-actions')
        ->name('manager.helpdesk.messages.forward');
    Route::get('/messages/{item}/info', [HelpdeskConversationsController::class, 'messageInfo'])
        ->middleware('throttle:helpdesk-msg-actions')
        ->name('manager.helpdesk.messages.info');

    // Conversation items
    Route::post('/conversation-items/{item}/react', [ConversationItemsController::class, 'react'])
        ->name('manager.helpdesk.conversation-items.react')
        ->middleware('throttle:30,1');
    Route::post('/conversation-items/{item}/retry-send', [ConversationItemsController::class, 'retrySend'])
        ->name('manager.helpdesk.conversation-items.retry-send')
        ->middleware('throttle:30,1');
    Route::delete('/conversation-items/{item}', [ConversationItemsController::class, 'destroy'])
        ->name('manager.helpdesk.conversation-items.destroy')
        ->middleware('throttle:30,1');

    // Agents management
    Route::prefix('agents')->name('manager.helpdesk.agents.')->group(function () {
        Route::get('/', [AgentsController::class, 'index'])->name('index');
        Route::get('{agent}', [AgentsController::class, 'show'])->name('show');
        Route::get('{agent}/profile-data', [AgentProfileController::class, 'show'])->name('profile-data')->middleware('throttle:60,1');
        Route::get('{agent}/edit', [AgentsController::class, 'edit'])->name('edit');
        Route::put('{agent}', [AgentsController::class, 'update'])->name('update');
    });

    // Reports — non-ticket-specific reports stay here; ticket reports
    // (index/export/trend) are owned by HelpdeskTickets
    // (see modules/HelpdeskTickets/routes/managers.php).
    Route::prefix('reports')->name('manager.helpdesk.reports.')->group(function () {
        Route::get('heatmap', [HeatmapReportController::class, 'index'])->name('heatmap');
        Route::get('agents', [AgentPerformanceController::class, 'index'])->name('agents');
        Route::get('trends', [TrendsReportController::class, 'index'])->name('trends');
        Route::get('trends/data', [TrendsReportController::class, 'data'])->name('trends.data');
        Route::get('csat', [CsatReportController::class, 'index'])->name('csat');
        Route::get('csat/data', [CsatReportController::class, 'data'])->name('csat.data');
        Route::get('at-risk', [AtRiskCustomersReportController::class, 'index'])->name('at-risk');
        Route::get('at-risk/data', [AtRiskCustomersReportController::class, 'data'])->name('at-risk.data');
        Route::get('sla-breaches', [SlaBreachesReportController::class, 'index'])->name('sla-breaches');
        Route::get('sla-breaches/data', [SlaBreachesReportController::class, 'data'])->name('sla-breaches.data');
    });

    // CSV exports — PII: además del authorize() de cada Form Request
    // (helpdesk.exports.create), el permiso se exige como middleware para
    // cortar en el router y no depender solo del request.
    Route::prefix('exports')->name('manager.helpdesk.exports.')->middleware(['can:helpdesk.exports.create', 'throttle:helpdesk-export'])->group(function () {
        Route::get('conversations', [ExportController::class, 'conversations'])->name('conversations');
        Route::get('customers', [ExportController::class, 'customers'])->name('customers');
        Route::get('csat', [ExportController::class, 'csat'])->name('csat');
    });

    // Customer 360 endpoint
    Route::get('/customers/{customer}/insights', [CustomerInsightsController::class, 'show'])
        ->name('manager.helpdesk.customers.insights');
    Route::get('/customers/{customer}/ecommerce', [CustomerEcommerceController::class, 'show'])
        ->name('manager.helpdesk.customers.ecommerce');

    // Perfil del cliente (modal inbox #09) — datos agregados
    Route::get('/customers/{customer}/profile-data', [CustomerProfileController::class, 'show'])
        ->name('manager.helpdesk.customers.profile-data');

    // Integraciones del cliente (PrestaShop, ERP, etc.) — movidas a HelpdeskIntegration/routes/managers.php

    // Settings routes moved to routes/settings.php (Patrón A: panel/settings/helpdesk/*)

    // Team Leaderboard
    Route::get('team/leaderboard', [LeaderboardController::class, 'index'])->name('manager.helpdesk.team.leaderboard');

    // GDPR panel
    Route::get('gdpr', [GdprController::class, 'panel'])->name('manager.helpdesk.gdpr.panel');

    // GDPR per-customer actions
    Route::prefix('customers/{customer}/gdpr')->name('manager.helpdesk.customers.gdpr.')->group(function () {
        Route::get('export', [GdprController::class, 'export'])->name('export');
        Route::post('delete', [GdprController::class, 'delete'])->name('delete');
    });

    // 2FA — code-checking endpoints are throttled to stop online brute force
    // of the 6-digit TOTP / recovery codes.
    Route::prefix('2fa')->name('manager.helpdesk.2fa.')->group(function () {
        Route::get('setup', [TwoFactorController::class, 'setup'])->name('setup');
        Route::get('challenge', [TwoFactorController::class, 'challenge'])->name('challenge');
        Route::post('enable', [TwoFactorController::class, 'enable'])->name('enable');
        Route::post('confirm', [TwoFactorController::class, 'confirm'])->name('confirm')->middleware('throttle:6,1');
        Route::post('verify', [TwoFactorController::class, 'verify'])->name('verify')->middleware('throttle:6,1');
        Route::post('disable', [TwoFactorController::class, 'disable'])->name('disable')->middleware('throttle:6,1');
    });
});
