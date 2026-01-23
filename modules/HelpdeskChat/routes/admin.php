<?php

/**
 * Chat Module - Admin Routes
 * Prefix: /admin/helpdesk
 * Middleware: auth, role:super-admin|admin
 */

use Illuminate\Support\Facades\Route;
// Contact Controllers
// TODO: Create CampaignsController
// use Modules\Chat\Http\Controllers\Admin\Campaign\CampaignsController as HelpdeskCampaignsController;
use Modules\HelpdeskChat\Http\Controllers\Admin\Contact\BulkContactController;
use Modules\HelpdeskChat\Http\Controllers\Admin\Contact\ContactAvatarController;
use Modules\HelpdeskChat\Http\Controllers\Admin\Contact\ContactController;
// Conversation Controllers
use Modules\HelpdeskChat\Http\Controllers\Admin\Contact\ContactFilterController;
use Modules\HelpdeskChat\Http\Controllers\Admin\Contact\ContactMergeController;
use Modules\HelpdeskChat\Http\Controllers\Admin\Contact\ContactSegmentController;
// Settings Controllers
use Modules\HelpdeskChat\Http\Controllers\Admin\Conversation\ConversationController;
use Modules\HelpdeskChat\Http\Controllers\Admin\Conversation\ConversationController as HelpdeskConversationsController;
use Modules\HelpdeskChat\Http\Controllers\Admin\Conversation\ConversationRoutingController;
use Modules\HelpdeskChat\Http\Controllers\Admin\Conversation\CustomersController as HelpdeskCustomersController;
use Modules\HelpdeskChat\Http\Controllers\Admin\Conversation\MessageController;
use Modules\HelpdeskChat\Http\Controllers\Admin\Helpcenter\HelpCenterController as HelpcenterController;
use Modules\HelpdeskChat\Http\Controllers\Admin\Reports\AgentPerformanceReportController;
use Modules\HelpdeskChat\Http\Controllers\Admin\Reports\AgentStatusController;
use Modules\HelpdeskChat\Http\Controllers\Admin\Reports\AnalyticsDashboardController;
use Modules\HelpdeskChat\Http\Controllers\Admin\Reports\ApiUsageDashboardController;
use Modules\HelpdeskChat\Http\Controllers\Admin\Reports\ContactReportController;
use Modules\HelpdeskChat\Http\Controllers\Admin\Reports\CsatReportController;
use Modules\HelpdeskChat\Http\Controllers\Admin\Reports\SlaReportController;
use Modules\HelpdeskChat\Http\Controllers\Admin\Settings\Ai\AiAgentFlowsController;
use Modules\HelpdeskChat\Http\Controllers\Admin\Settings\Ai\AiAgentSettingsController;
use Modules\HelpdeskChat\Http\Controllers\Admin\Settings\AttributesController;
use Modules\HelpdeskChat\Http\Controllers\Admin\Settings\AuditLogController;
use Modules\HelpdeskChat\Http\Controllers\Admin\Settings\AutomationController;
use Modules\HelpdeskChat\Http\Controllers\Admin\Settings\CannedController;
use Modules\HelpdeskChat\Http\Controllers\Admin\Settings\Channels\ApiController;
use Modules\HelpdeskChat\Http\Controllers\Admin\Settings\Channels\ChannelCredentialsController;
use Modules\HelpdeskChat\Http\Controllers\Admin\Settings\Channels\ChannelsController;
use Modules\HelpdeskChat\Http\Controllers\Admin\Settings\Channels\EmailController;
use Modules\HelpdeskChat\Http\Controllers\Admin\Settings\Channels\FacebookController;
use Modules\HelpdeskChat\Http\Controllers\Admin\Settings\Channels\InstagramController;
use Modules\HelpdeskChat\Http\Controllers\Admin\Settings\Channels\SmsController;
use Modules\HelpdeskChat\Http\Controllers\Admin\Settings\Channels\WebController;
use Modules\HelpdeskChat\Http\Controllers\Admin\Settings\Channels\WhatsappController;
use Modules\HelpdeskChat\Http\Controllers\Admin\Settings\Contact\CustomAttributeDefinitionController;
use Modules\HelpdeskChat\Http\Controllers\Admin\Settings\EmailTemplateController;
// Reports Controllers
use Modules\HelpdeskChat\Http\Controllers\Admin\Settings\HourController;
use Modules\HelpdeskChat\Http\Controllers\Admin\Settings\IntegrationController;
use Modules\HelpdeskChat\Http\Controllers\Admin\Settings\LabelController;
use Modules\HelpdeskChat\Http\Controllers\Admin\Settings\MacroController;
use Modules\HelpdeskChat\Http\Controllers\Admin\Settings\MessageTemplateController;
// Agent Controllers (moved to Reports)
// Note: AgentStatusController is in Reports, not Agent directory

// Channel Controllers
use Modules\HelpdeskChat\Http\Controllers\Admin\Settings\NotificationSettingsController;
use Modules\HelpdeskChat\Http\Controllers\Admin\Settings\SavedViewController;
use Modules\HelpdeskChat\Http\Controllers\Admin\Settings\SettingsController as SettingsHelpdeskController;
use Modules\HelpdeskChat\Http\Controllers\Admin\Settings\SlaPolicyController;
use Modules\HelpdeskChat\Http\Controllers\Admin\Settings\StatusesController;
use Modules\HelpdeskChat\Http\Controllers\Admin\Settings\TagsController;
use Modules\HelpdeskChat\Http\Controllers\Admin\Settings\Team\TeamController;
use Modules\HelpdeskChat\Http\Controllers\Admin\Settings\Team\TeamRoleController;
// AI Controllers
use Modules\HelpdeskChat\Http\Controllers\Admin\Settings\Team\TeamWorkloadBalancingController;
use Modules\HelpdeskChat\Http\Controllers\Admin\Settings\TicketCannedRepliesController;
// Helpcenter Controller
use Modules\HelpdeskChat\Http\Controllers\Admin\Settings\TicketCategoriesController;
// Campaigns Controller
use Modules\HelpdeskChat\Http\Controllers\Admin\Settings\TicketGroupsController;
// Dashboard Controller

// Conversation Area Controllers
use Modules\HelpdeskChat\Http\Controllers\Admin\Settings\TicketSlaPoliciesController;
use Modules\HelpdeskChat\Http\Controllers\Admin\Settings\TicketStatusesController;
// Additional Reports
use Modules\HelpdeskChat\Http\Controllers\Admin\Settings\TicketViewsController;
use Modules\HelpdeskChat\Http\Controllers\Admin\Settings\WebhookController;
// Ticket Module Controllers (cross-module imports)
use Modules\HelpdeskTicket\Http\Controllers\Helpdesk\TicketCommentsController;
use Modules\HelpdeskTicket\Http\Controllers\Helpdesk\TicketNotesController;
use Modules\HelpdeskTicket\Http\Controllers\Helpdesk\TicketsController as HelpdeskTicketsController;

// Contacts
Route::resource('contacts', ContactController::class);
Route::post('contacts/import', [ContactController::class, 'import'])->name('contacts.import');
Route::get('contacts/export', [ContactController::class, 'export'])->name('contacts.export');

// Inboxes (Channels) - TODO: Create InboxController or use ChannelsController
// Route::resource('inboxes', InboxController::class);
// Route::post('inboxes/{inbox}/toggle-status', [InboxController::class, 'toggleStatus'])
//     ->name('inboxes.toggle-status');

// Teams
Route::resource('teams', TeamController::class);
Route::post('teams/{team}/members', [TeamController::class, 'addMember'])->name('teams.members.add');
Route::delete('teams/{team}/members/{user}', [TeamController::class, 'removeMember'])
    ->name('teams.members.remove');

// TODO: Legacy routes block - uses wrong prefix 'helpdeskss' and 'manager.helpdesk.*' names
// This entire block (lines 104-410) should be reviewed and likely removed
// The correct routes are defined in the 'helpdesk' group starting at line ~707
/*
Route::group(['prefix' => 'helpdeskss'], function () {

    // Main Groups Index
    Route::get('/', [HelpdeskCustomersController::class, 'index'])->name('manager.helpdesk');

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

    // Conversations
    Route::get('/conversation', [HelpdeskConversationsController::class, 'index'])->name('manager.helpdesk.conversation.index');
    Route::get('/conversation/create', [HelpdeskConversationsController::class, 'create'])->name('manager.helpdesk.conversation.create');
    Route::post('/conversation', [HelpdeskConversationsController::class, 'store'])->name('manager.helpdesk.conversation.store');
    Route::get('/conversation/{conversation}', [HelpdeskConversationsController::class, 'show'])->name('manager.helpdesk.conversation.show');
    Route::get('/conversation/{conversation}/edit', [HelpdeskConversationsController::class, 'edit'])->name('manager.helpdesk.conversation.edit');
    Route::put('/conversation/{conversation}', [HelpdeskConversationsController::class, 'update'])->name('manager.helpdesk.conversation.update');
    Route::delete('/conversation/{conversation}', [HelpdeskConversationsController::class, 'destroy'])->name('manager.helpdesk.conversation.destroy');
    Route::post('/conversation/{conversation}/restore', [HelpdeskConversationsController::class, 'restore'])->name('manager.helpdesk.conversation.restore');
    Route::delete('/conversation/{conversation}/force-delete', [HelpdeskConversationsController::class, 'forceDelete'])->name('manager.helpdesk.conversation.forceDelete');
    Route::post('/conversation/{conversation}/close', [HelpdeskConversationsController::class, 'close'])->name('manager.helpdesk.conversation.close');
    Route::post('/conversation/{conversation}/reopen', [HelpdeskConversationsController::class, 'reopen'])->name('manager.helpdesk.conversation.reopen');
    Route::post('/conversation/{conversation}/archive', [HelpdeskConversationsController::class, 'archive'])->name('manager.helpdesk.conversation.archive');
    Route::post('/conversation/{conversation}/unarchive', [HelpdeskConversationsController::class, 'unarchive'])->name('manager.helpdesk.conversation.unarchive');
    Route::post('/conversation/{conversation}/messages', [HelpdeskConversationsController::class, 'storeMessage'])->name('manager.helpdesk.conversation.messages.store');

    // Tickets
    Route::get('/tickets', [HelpdeskTicketsController::class, 'index'])->name('manager.helpdesk.tickets.index');
    Route::get('/tickets/create', [HelpdeskTicketsController::class, 'create'])->name('manager.helpdesk.tickets.create');
    Route::post('/tickets', [HelpdeskTicketsController::class, 'store'])->name('manager.helpdesk.tickets.store');
    Route::get('/tickets/{ticket}', [HelpdeskTicketsController::class, 'show'])->name('manager.helpdesk.tickets.show');
    Route::get('/tickets/{ticket}/edit', [HelpdeskTicketsController::class, 'edit'])->name('manager.helpdesk.tickets.edit');
    Route::put('/tickets/{ticket}', [HelpdeskTicketsController::class, 'update'])->name('manager.helpdesk.tickets.update');
    Route::delete('/tickets/{ticket}', [HelpdeskTicketsController::class, 'destroy'])->name('manager.helpdesk.tickets.destroy');
    Route::post('/tickets/{ticket}/close', [HelpdeskTicketsController::class, 'close'])->name('manager.helpdesk.tickets.close');
    Route::post('/tickets/{ticket}/resolve', [HelpdeskTicketsController::class, 'resolve'])->name('manager.helpdesk.tickets.resolve');
    Route::post('/tickets/{ticket}/reopen', [HelpdeskTicketsController::class, 'reopen'])->name('manager.helpdesk.tickets.reopen');
    Route::post('/tickets/{ticket}/archive', [HelpdeskTicketsController::class, 'archive'])->name('manager.helpdesk.tickets.archive');
    Route::post('/tickets/{ticket}/messages', [HelpdeskTicketsController::class, 'storeMessage'])->name('manager.helpdesk.tickets.messages.store');

    // Ticket Comments
    Route::get('/tickets/{ticket}/comments', [TicketCommentsController::class, 'index'])->name('manager.helpdesk.tickets.comments.index');
    Route::post('/tickets/{ticket}/comments', [TicketCommentsController::class, 'store'])->name('manager.helpdesk.tickets.comments.store');
    Route::get('/tickets/{ticket}/comments/{comment}', [TicketCommentsController::class, 'show'])->name('manager.helpdesk.tickets.comments.show');
    Route::put('/tickets/{ticket}/comments/{comment}', [TicketCommentsController::class, 'update'])->name('manager.helpdesk.tickets.comments.update');
    Route::delete('/tickets/{ticket}/comments/{comment}', [TicketCommentsController::class, 'destroy'])->name('manager.helpdesk.tickets.comments.destroy');
    Route::post('/tickets/{ticket}/comments/{comment}/restore', [TicketCommentsController::class, 'restore'])->name('manager.helpdesk.tickets.comments.restore');

    // Ticket Notes
    Route::get('/tickets/{ticket}/notes', [TicketNotesController::class, 'index'])->name('manager.helpdesk.tickets.notes.index');
    Route::post('/tickets/{ticket}/notes', [TicketNotesController::class, 'store'])->name('manager.helpdesk.tickets.notes.store');
    Route::get('/tickets/{ticket}/notes/{note}', [TicketNotesController::class, 'show'])->name('manager.helpdesk.tickets.notes.show');
    Route::put('/tickets/{ticket}/notes/{note}', [TicketNotesController::class, 'update'])->name('manager.helpdesk.tickets.notes.update');
    Route::delete('/tickets/{ticket}/notes/{note}', [TicketNotesController::class, 'destroy'])->name('manager.helpdesk.tickets.notes.destroy');
    Route::post('/tickets/{ticket}/notes/{note}/pin', [TicketNotesController::class, 'pin'])->name('manager.helpdesk.tickets.notes.pin');
    Route::post('/tickets/{ticket}/notes/{note}/color', [TicketNotesController::class, 'changeColor'])->name('manager.helpdesk.tickets.notes.color');
    Route::post('/tickets/{ticket}/notes/{note}/restore', [TicketNotesController::class, 'restore'])->name('manager.helpdesk.tickets.notes.restore');

    // TODO: Create CampaignsController
    // Campaigns
    // Route::get('/campaigns', [HelpdeskCampaignsController::class, 'index'])->name('manager.helpdesk.campaigns.index');
    // Route::get('/campaigns/templates', [HelpdeskCampaignsController::class, 'templates'])->name('manager.helpdesk.campaigns.templates');
    // Route::get('/campaigns/create', [HelpdeskCampaignsController::class, 'create'])->name('manager.helpdesk.campaigns.create');
    // Route::post('/campaigns', [HelpdeskCampaignsController::class, 'store'])->name('manager.helpdesk.campaigns.store');
    // Route::get('/campaigns/{campaign}', [HelpdeskCampaignsController::class, 'show'])->name('manager.helpdesk.campaigns.show');
    // Route::get('/campaigns/{campaign}/edit', [HelpdeskCampaignsController::class, 'edit'])->name('manager.helpdesk.campaigns.edit');
    // Route::put('/campaigns/{campaign}', [HelpdeskCampaignsController::class, 'update'])->name('manager.helpdesk.campaigns.update');
    // Route::delete('/campaigns/{campaign}', [HelpdeskCampaignsController::class, 'destroy'])->name('manager.helpdesk.campaigns.destroy');
    // Route::post('/campaigns/{campaign}/publish', [HelpdeskCampaignsController::class, 'publish'])->name('manager.helpdesk.campaigns.publish');
    // Route::post('/campaigns/{campaign}/pause', [HelpdeskCampaignsController::class, 'pause'])->name('manager.helpdesk.campaigns.pause');
    // Route::post('/campaigns/{campaign}/resume', [HelpdeskCampaignsController::class, 'resume'])->name('manager.helpdesk.campaigns.resume');
    // Route::post('/campaigns/{campaign}/end', [HelpdeskCampaignsController::class, 'end'])->name('manager.helpdesk.campaigns.end');
    // Route::get('/campaigns/{campaign}/statistics', [HelpdeskCampaignsController::class, 'statistics'])->name('manager.helpdesk.campaigns.statistics');
    // Route::post('/campaigns/{campaign}/duplicate', [HelpdeskCampaignsController::class, 'duplicate'])->name('manager.helpdesk.campaigns.duplicate');

    // AI Agent
    Route::prefix('ai')->group(function () {
        // Settings
        Route::get('settings', [AiAgentSettingsController::class, 'index'])->name('manager.helpdesk.ai.settings');
        Route::put('settings', [AiAgentSettingsController::class, 'update'])->name('manager.helpdesk.ai.settings.update');
        Route::post('settings/test-connection', [AiAgentSettingsController::class, 'testConnection'])->name('manager.helpdesk.ai.settings.test');
        Route::post('settings/get-models', [AiAgentSettingsController::class, 'getModels'])->name('manager.helpdesk.ai.settings.get-models');
        Route::get('settings/statistics', [AiAgentSettingsController::class, 'statistics'])->name('manager.helpdesk.ai.settings.statistics');

        // Tags
        Route::get('tags', [AiAgentSettingsController::class, 'tagsIndex'])->name('manager.helpdesk.ai.tags.index');
        Route::post('tags', [AiAgentSettingsController::class, 'tagsStore'])->name('manager.helpdesk.ai.tags.store');
        Route::put('tags/{tag}', [AiAgentSettingsController::class, 'tagsUpdate'])->name('manager.helpdesk.ai.tags.update');
        Route::delete('tags/{tag}', [AiAgentSettingsController::class, 'tagsDestroy'])->name('manager.helpdesk.ai.tags.destroy');
        Route::post('tags/{tag}/toggle', [AiAgentSettingsController::class, 'tagsToggle'])->name('manager.helpdesk.ai.tags.toggle');

        // Tools
        Route::get('tools', [AiAgentSettingsController::class, 'toolsIndex'])->name('manager.helpdesk.ai.tools.index');
        Route::post('tools', [AiAgentSettingsController::class, 'toolsStore'])->name('manager.helpdesk.ai.tools.store');
        Route::put('tools/{tool}', [AiAgentSettingsController::class, 'toolsUpdate'])->name('manager.helpdesk.ai.tools.update');
        Route::delete('tools/{tool}', [AiAgentSettingsController::class, 'toolsDestroy'])->name('manager.helpdesk.ai.tools.destroy');
        Route::post('tools/{tool}/toggle', [AiAgentSettingsController::class, 'toolsToggle'])->name('manager.helpdesk.ai.tools.toggle');

        // Knowledge Base
        Route::get('knowledge', [AiAgentSettingsController::class, 'knowledgeIndex'])->name('manager.helpdesk.ai.knowledge.index');
        Route::post('knowledge', [AiAgentSettingsController::class, 'knowledgeStore'])->name('manager.helpdesk.ai.knowledge.store');
        Route::put('knowledge/{knowledge}', [AiAgentSettingsController::class, 'knowledgeUpdate'])->name('manager.helpdesk.ai.knowledge.update');
        Route::delete('knowledge/{knowledge}', [AiAgentSettingsController::class, 'knowledgeDestroy'])->name('manager.helpdesk.ai.knowledge.destroy');
        Route::post('knowledge/{knowledge}/toggle', [AiAgentSettingsController::class, 'knowledgeToggle'])->name('manager.helpdesk.ai.knowledge.toggle');
        Route::post('knowledge/{knowledge}/generate-embedding', [AiAgentSettingsController::class, 'knowledgeGenerateEmbedding'])->name('manager.helpdesk.ai.knowledge.generate-embedding');

        // Flows
        Route::get('/', [AiAgentFlowsController::class, 'index'])->name('manager.helpdesk.ai.flows.index');
        Route::get('/create', [AiAgentFlowsController::class, 'create'])->name('manager.helpdesk.ai.flows.create');
        Route::post('/', [AiAgentFlowsController::class, 'store'])->name('manager.helpdesk.ai.flows.store');
        Route::get('/{flow}', [AiAgentFlowsController::class, 'show'])->name('manager.helpdesk.ai.flows.show');
        Route::get('/{flow}/edit', [AiAgentFlowsController::class, 'edit'])->name('manager.helpdesk.ai.flows.edit');
        Route::put('/{flow}', [AiAgentFlowsController::class, 'update'])->name('manager.helpdesk.ai.flows.update');
        Route::delete('/{flow}', [AiAgentFlowsController::class, 'destroy'])->name('manager.helpdesk.ai.flows.destroy');
        Route::post('flows/{flow}/publish', [AiAgentFlowsController::class, 'publish'])->name('manager.helpdesk.ai.flows.publish');
        Route::post('flows/{flow}/archive', [AiAgentFlowsController::class, 'archive'])->name('manager.helpdesk.ai.flows.archive');
        Route::post('flows/{flow}/duplicate', [AiAgentFlowsController::class, 'duplicate'])->name('manager.helpdesk.ai.flows.duplicate');

        // Flow Nodes
        Route::post('flows/{flow}/nodes', [AiAgentFlowsController::class, 'storeNode'])->name('manager.helpdesk.ai.flows.nodes.store');
        Route::put('flows/{flow}/nodes/{node}', [AiAgentFlowsController::class, 'updateNode'])->name('manager.helpdesk.ai.flows.nodes.update');
        Route::delete('flows/{flow}/nodes/{node}', [AiAgentFlowsController::class, 'deleteNode'])->name('manager.helpdesk.ai.flows.nodes.delete');

        // Flow Structure
        Route::put('flows/{flow}/structure', [AiAgentFlowsController::class, 'updateStructure'])->name('manager.helpdesk.ai.flows.structure');
    });

    // Settings
    Route::prefix('settings')->name('manager.helpdesk.settings.')->group(function () {
        // Tickets Settings
        Route::get('tickets', [SettingsHelpdeskController::class, 'ticketsIndex'])->name('tickets');
        Route::put('tickets', [SettingsHelpdeskController::class, 'ticketsUpdate'])->name('tickets.update');

        // LiveChat Settings (New Groups Widget)
        Route::get('livechat', [SettingsHelpdeskController::class, 'livechatIndex'])->name('livechat');
        Route::put('livechat', [SettingsHelpdeskController::class, 'livechatUpdate'])->name('livechat.update');

        // AI Settings
        Route::get('ai', [SettingsHelpdeskController::class, 'aiIndex'])->name('ai');
        Route::put('ai', [SettingsHelpdeskController::class, 'aiUpdate'])->name('ai.update');

        // Uploading Settings
        Route::get('uploading', [SettingsHelpdeskController::class, 'uploadingIndex'])->name('uploading');
        Route::put('uploading', [SettingsHelpdeskController::class, 'uploadingUpdate'])->name('uploading.update');

        // Customers Settings (link to existing customers routes)
        Route::get('customers', [HelpdeskCustomersController::class, 'index'])->name('customers');

        // Tickets Settings
        Route::prefix('tickets')->name('tickets.')->group(function () {
            // Categories
            Route::prefix('categories')->name('categories.')->group(function () {
                Route::get('/', [TicketCategoriesController::class, 'index'])->name('index');
                Route::get('create', [TicketCategoriesController::class, 'create'])->name('create');
                Route::post('/', [TicketCategoriesController::class, 'store'])->name('store');
                Route::get('{category}/edit', [TicketCategoriesController::class, 'edit'])->name('edit');
                Route::put('{category}', [TicketCategoriesController::class, 'update'])->name('update');
                Route::patch('{category}/toggle', [TicketCategoriesController::class, 'toggle'])->name('toggle');
                Route::delete('{category}', [TicketCategoriesController::class, 'destroy'])->name('destroy');
                Route::post('reorder', [TicketCategoriesController::class, 'reorder'])->name('reorder');
            });

            // Groups
            Route::prefix('groups')->name('groups.')->group(function () {
                Route::get('/', [TicketGroupsController::class, 'index'])->name('index');
                Route::get('create', [TicketGroupsController::class, 'create'])->name('create');
                Route::post('/', [TicketGroupsController::class, 'store'])->name('store');
                Route::get('{group}/edit', [TicketGroupsController::class, 'edit'])->name('edit');
                Route::put('{group}', [TicketGroupsController::class, 'update'])->name('update');
                Route::patch('{group}/toggle', [TicketGroupsController::class, 'toggle'])->name('toggle');
                Route::delete('{group}', [TicketGroupsController::class, 'destroy'])->name('destroy');
                Route::post('reorder', [TicketGroupsController::class, 'reorder'])->name('reorder');
            });

            // Canned Replies
            Route::prefix('canned-replies')->name('canned-replies.')->group(function () {
                Route::get('/', [TicketCannedRepliesController::class, 'index'])->name('index');
                Route::get('create', [TicketCannedRepliesController::class, 'create'])->name('create');
                Route::post('/', [TicketCannedRepliesController::class, 'store'])->name('store');
                Route::get('{reply}/edit', [TicketCannedRepliesController::class, 'edit'])->name('edit');
                Route::put('{reply}', [TicketCannedRepliesController::class, 'update'])->name('update');
                Route::delete('{reply}', [TicketCannedRepliesController::class, 'destroy'])->name('destroy');
            });

            // Statuses
            Route::prefix('statuses')->name('statuses.')->group(function () {
                Route::get('/', [TicketStatusesController::class, 'index'])->name('index');
                Route::get('create', [TicketStatusesController::class, 'create'])->name('create');
                Route::post('/', [TicketStatusesController::class, 'store'])->name('store');
                Route::get('{status}/edit', [TicketStatusesController::class, 'edit'])->name('edit');
                Route::put('{status}', [TicketStatusesController::class, 'update'])->name('update');
                Route::delete('{status}', [TicketStatusesController::class, 'destroy'])->name('destroy');
                Route::post('reorder', [TicketStatusesController::class, 'reorder'])->name('reorder');
            });

            // SLA Policies
            Route::prefix('sla-policies')->name('sla-policies.')->group(function () {
                Route::get('/', [TicketSlaPoliciesController::class, 'index'])->name('index');
                Route::get('create', [TicketSlaPoliciesController::class, 'create'])->name('create');
                Route::post('/', [TicketSlaPoliciesController::class, 'store'])->name('store');
                Route::get('{policy}/edit', [TicketSlaPoliciesController::class, 'edit'])->name('edit');
                Route::put('{policy}', [TicketSlaPoliciesController::class, 'update'])->name('update');
                Route::patch('{policy}/toggle', [TicketSlaPoliciesController::class, 'toggle'])->name('toggle');
                Route::delete('{policy}', [TicketSlaPoliciesController::class, 'destroy'])->name('destroy');
            });

            // Views
            Route::prefix('views')->name('views.')->group(function () {
                Route::get('/', [TicketViewsController::class, 'index'])->name('index');
                Route::get('create', [TicketViewsController::class, 'create'])->name('create');
                Route::post('/', [TicketViewsController::class, 'store'])->name('store');
                Route::get('{view}/edit', [TicketViewsController::class, 'edit'])->name('edit');
                Route::put('{view}', [TicketViewsController::class, 'update'])->name('update');
                Route::delete('{view}', [TicketViewsController::class, 'destroy'])->name('destroy');
                Route::post('reorder', [TicketViewsController::class, 'reorder'])->name('reorder');
            });

            // Team Settings
            Route::prefix('team')->name('team.')->group(function () {
                // Members
                Route::get('members', [TeamController::class, 'membersIndex'])->name('members');
                Route::get('members/{id}/edit', [TeamController::class, 'memberEdit'])->name('member.edit');
                Route::put('members/{id}', [TeamController::class, 'memberUpdate'])->name('member.update');

                // Groups
                Route::get('groups', [TeamController::class, 'groupsIndex'])->name('groups');
                Route::get('groups/create', [TeamController::class, 'groupCreate'])->name('group.create');
                Route::post('groups', [TeamController::class, 'groupStore'])->name('group.store');
                Route::get('groups/{id}/edit', [TeamController::class, 'groupEdit'])->name('group.edit');
                Route::put('groups/{id}', [TeamController::class, 'groupUpdate'])->name('group.update');
                Route::delete('groups/{id}', [TeamController::class, 'groupDestroy'])->name('group.destroy');
            });

            // Attributes Settings
            Route::prefix('attributes')->name('attributes.')->group(function () {
                Route::get('/', [AttributesController::class, 'index'])->name('index');
                Route::get('create', [AttributesController::class, 'create'])->name('create');
                Route::post('/', [AttributesController::class, 'store'])->name('store');
                Route::get('{id}/edit', [AttributesController::class, 'edit'])->name('edit');
                Route::put('{id}', [AttributesController::class, 'update'])->name('update');
                Route::delete('{id}', [AttributesController::class, 'destroy'])->name('destroy');
                Route::patch('{id}/toggle', [AttributesController::class, 'toggleActive'])->name('toggle');
            });

            // Tags Settings
            Route::prefix('tags')->name('tags.')->group(function () {
                Route::get('/', [TagsController::class, 'index'])->name('index');
                Route::get('create', [TagsController::class, 'create'])->name('create');
                Route::post('/', [TagsController::class, 'store'])->name('store');
                Route::get('{tag}/edit', [TagsController::class, 'edit'])->name('edit');
                Route::put('{tag}', [TagsController::class, 'update'])->name('update');
                Route::delete('{tag}', [TagsController::class, 'destroy'])->name('destroy');
            });

            // Conversations Statuses Settings
            Route::prefix('statuses')->name('statuses.')->group(function () {
                Route::get('/', [StatusesController::class, 'index'])->name('index');
                Route::get('create', [StatusesController::class, 'create'])->name('create');
                Route::post('/', [StatusesController::class, 'store'])->name('store');
                Route::get('{status}/edit', [StatusesController::class, 'edit'])->name('edit');
                Route::put('{status}', [StatusesController::class, 'update'])->name('update');
                Route::delete('{status}', [StatusesController::class, 'destroy'])->name('destroy');
                Route::post('{status}/toggle', [StatusesController::class, 'toggle'])->name('toggle');
                Route::post('reorder', [StatusesController::class, 'reorder'])->name('reorder');
            });
        });
    });

    // Help Center Manager
    Route::prefix('helpcenter')->group(function () {
        // Main Index
        Route::get('/', [HelpcenterController::class, 'index'])->name('manager.helpdesk.helpcenter.index');

        // Categories
        Route::get('/categories', [HelpcenterController::class, 'index'])->name('manager.helpdesk.helpcenter.categories');
        Route::get('/categories/create', [HelpcenterController::class, 'create'])->name('manager.helpdesk.helpcenter.categories.create');
        Route::post('/categories/store', [HelpcenterController::class, 'store'])->name('manager.helpdesk.helpcenter.categories.store');
        Route::get('/categories/{id}', [HelpcenterController::class, 'showCategory'])->name('manager.helpdesk.helpcenter.categories.show');
        Route::get('/categories/edit/{id}', [HelpcenterController::class, 'edit'])->name('manager.helpdesk.helpcenter.categories.edit');
        Route::post('/categories/update', [HelpcenterController::class, 'update'])->name('manager.helpdesk.helpcenter.categories.update');
        Route::get('/categories/destroy/{id}', [HelpcenterController::class, 'destroy'])->name('manager.helpdesk.helpcenter.categories.destroy');

        // Sections
        Route::get('/sections/create', [HelpcenterController::class, 'createSection'])->name('manager.helpdesk.helpcenter.sections.create');
        Route::post('/sections/store', [HelpcenterController::class, 'storeSection'])->name('manager.helpdesk.helpcenter.sections.store');
        Route::get('/sections/{id}', [HelpcenterController::class, 'showSection'])->name('manager.helpdesk.helpcenter.sections.show');
        Route::get('/sections/{id}/edit', [HelpcenterController::class, 'editSection'])->name('manager.helpdesk.helpcenter.sections.edit');
        Route::post('/sections/update', [HelpcenterController::class, 'updateSection'])->name('manager.helpdesk.helpcenter.sections.update');
        Route::get('/sections/{id}/destroy', [HelpcenterController::class, 'destroySection'])->name('manager.helpdesk.helpcenter.sections.destroy');
        Route::get('/sections/{id}/articles/create', [HelpcenterController::class, 'createArticleInSection'])->name('manager.helpdesk.helpcenter.sections.articles.create');

        // Articles
        Route::get('/articles', [HelpcenterController::class, 'articlesIndex'])->name('manager.helpdesk.helpcenter.articles');
        Route::get('/articles/create', [HelpcenterController::class, 'createArticle'])->name('manager.helpdesk.helpcenter.articles.create');
        Route::post('/articles/store', [HelpcenterController::class, 'storeArticle'])->name('manager.helpdesk.helpcenter.articles.store');
        Route::get('/articles/edit/{id}', [HelpcenterController::class, 'editArticle'])->name('manager.helpdesk.helpcenter.articles.edit');
        Route::post('/articles/update', [HelpcenterController::class, 'updateArticle'])->name('manager.helpdesk.helpcenter.articles.update');
        Route::get('/articles/destroy/{id}', [HelpcenterController::class, 'destroyArticle'])->name('manager.helpdesk.helpcenter.articles.destroy');
    });
});
*/
// END Legacy routes block

// API Usage Dashboard
Route::prefix('api-usage')->name('api-usage.')->group(function () {
    Route::get('/', [ApiUsageDashboardController::class, 'index'])->name('index');
    Route::get('/data', [ApiUsageDashboardController::class, 'getData'])->name('data');
});

// Channels
Route::prefix('channels')->name('channels.')->group(function () {
    // Channels Dashboard
    Route::get('/', [ChannelsController::class, 'dashboard'])->name('dashboard');

    // Web Widgets - Full CRUD
    Route::resource('webs', WebController::class);

    // Facebook Pages
    Route::get('/facebook-pages/callback', [FacebookController::class, 'callback'])->name('facebook-pages.callback');
    Route::get('/facebook-pages/select', [FacebookController::class, 'select'])->name('facebook-pages.select');
    Route::post('/facebook-pages/{facebookPage}/reauthorize', [FacebookController::class, 'reauthorize'])->name('facebook-pages.reauthorize');
    Route::resource('facebook-pages', FacebookController::class);

    // Instagram
    Route::get('/instagrams/callback', [InstagramController::class, 'callback'])->name('instagrams.callback');
    Route::get('/instagrams/select', [InstagramController::class, 'select'])->name('instagrams.select');
    Route::post('/instagrams/{instagram}/refresh-token', [InstagramController::class, 'refreshToken'])->name('instagrams.refreshToken');
    Route::post('/instagrams/{instagram}/reauthorize', [InstagramController::class, 'reauthorize'])->name('instagrams.reauthorize');
    Route::resource('instagrams', InstagramController::class);

    // WhatsApp
    Route::post('/whatsapps/{whatsapp}/sync-templates', [WhatsappController::class, 'syncTemplates'])->name('whatsapps.sync-templates');
    Route::get('/whatsapps/{whatsapp}/connection-status', [WhatsappController::class, 'connectionStatus'])->name('whatsapps.connection-status');
    Route::get('/whatsapps/{whatsapp}/qr-code', [WhatsappController::class, 'qrCode'])->name('whatsapps.qr-code');
    Route::get('/whatsapps/{whatsapp}/settings', [WhatsappController::class, 'settings'])->name('whatsapps.settings');
    Route::put('/whatsapps/{whatsapp}/settings', [WhatsappController::class, 'updateSettings'])->name('whatsapps.update-settings');
    Route::post('/whatsapps/{whatsapp}/logout', [WhatsappController::class, 'logout'])->name('whatsapps.logout');
    Route::post('/whatsapps/{whatsapp}/restart', [WhatsappController::class, 'restart'])->name('whatsapps.restart');
    Route::post('/whatsapps/{whatsapp}/sync-contacts', [WhatsappController::class, 'syncContacts'])->name('whatsapps.sync-contacts');
    Route::post('/whatsapps/{whatsapp}/sync-chats', [WhatsappController::class, 'syncChats'])->name('whatsapps.sync-chats');
    Route::post('/whatsapps/{whatsapp}/sync-messages', [WhatsappController::class, 'syncMessages'])->name('whatsapps.sync-messages');
    Route::resource('whatsapps', WhatsappController::class);

    // Email
    Route::post('/emails/{email}/test-smtp', [EmailController::class, 'testSmtp'])->name('emails.testSmtp');
    Route::resource('emails', EmailController::class);

    // SMS
    Route::post('/sms/{sms}/test-connection', [SmsController::class, 'testConnection'])->name('sms.test-connection');
    Route::resource('sms', SmsController::class);

    // API
    Route::post('/api/{api}/test-connection', [ApiController::class, 'testConnection'])->name('api.test-connection');
    Route::post('/api/{api}/regenerate-hmac', [ApiController::class, 'regenerateHmacToken'])->name('api.regenerate-hmac');
    Route::resource('api', ApiController::class);
});

// Conversations
Route::prefix('conversation')->name('conversation.')->group(function () {
    // Dashboard style filters
    Route::get('/', [ConversationController::class, 'index'])->name('index'); // Todas las conversaciones
    Route::get('/mine', [ConversationController::class, 'mine'])->name('mine'); // Asignadas a mí
    Route::get('/unassigned', [ConversationController::class, 'unassigned'])->name('unassigned'); // Sin asignar
    Route::get('/mentions', [ConversationController::class, 'mentions'])->name('mentions'); // Donde me mencionaron
    Route::get('/unattended', [ConversationController::class, 'unattended'])->name('unattended'); // Desatendidas

    // Filtros por recurso
    Route::get('/inbox/{inbox}', [ConversationController::class, 'byInbox'])->name('byInbox'); // Por canal
    Route::get('/team/{team}', [ConversationController::class, 'byTeam'])->name('byTeam'); // Por equipo
    Route::get('/label/{label}', [ConversationController::class, 'byLabel'])->name('byLabel'); // Por etiqueta

    // Export conversation to Excel/CSV
    Route::get('/export-excel', [ConversationController::class, 'exportToExcel'])->name('exportExcel');

    // CRUD operations
    Route::post('/', [ConversationController::class, 'store'])->name('store');
    Route::get('/{conversation}', [ConversationController::class, 'show'])->name('show');
    Route::put('/{conversation}', [ConversationController::class, 'update'])->name('update');
    Route::delete('/{conversation}', [ConversationController::class, 'destroy'])->name('destroy');

    // Export individual conversation
    Route::get('/{conversation}/export-pdf', [ConversationController::class, 'exportToPdf'])->name('exportPdf');
    Route::get('/{conversation}/print', [ConversationController::class, 'printView'])->name('print');
    Route::post('/{conversation}/email-transcript', [ConversationController::class, 'emailTranscript'])->name('emailTranscript');

    // Acciones sobre conversaciones
    Route::patch('/{conversation}/status', [ConversationController::class, 'updateStatus'])->name('updateStatus');
    Route::patch('/{conversation}/assign', [ConversationController::class, 'assign'])->name('assign');
    Route::patch('/{conversation}/team', [ConversationController::class, 'updateTeam'])->name('updateTeam');
    Route::patch('/{conversation}/priority', [ConversationController::class, 'updatePriority'])->name('updatePriority');
    Route::post('/{conversation}/labels', [ConversationController::class, 'addLabels'])->name('addLabels');
    Route::delete('/{conversation}/labels', [ConversationController::class, 'removeLabel'])->name('removeLabel');
    Route::post('/{conversation}/snooze', [ConversationController::class, 'snooze'])->name('snooze');
    Route::post('/{conversation}/unsnooze', [ConversationController::class, 'unsnooze'])->name('unsnooze');
    Route::post('/{conversation}/close', [ConversationController::class, 'close'])->name('close');
    Route::post('/{conversation}/reopen', [ConversationController::class, 'reopen'])->name('reopen');

    // Messages within a conversation
    Route::post('/{conversation}/messages', [MessageController::class, 'store'])->name('messages.store');

    // Typing indicator
    Route::post('/{conversation}/typing', [ConversationController::class, 'typing'])->name('typing');

    // Conversations Routing
    Route::post('/{conversation}/auto-assign', [ConversationRoutingController::class, 'autoAssign'])->name('auto-assign');
    Route::get('/{conversation}/suggest-agent', [ConversationRoutingController::class, 'suggest'])->name('suggest-agent');
});

// Saved Views
Route::prefix('saved-views')->name('saved-views.')->group(function () {
    Route::get('/', [SavedViewController::class, 'index'])->name('index');
    Route::post('/', [SavedViewController::class, 'store'])->name('store');
    Route::get('/{savedView}', [SavedViewController::class, 'show'])->name('show');
    Route::put('/{savedView}', [SavedViewController::class, 'update'])->name('update');
    Route::delete('/{savedView}', [SavedViewController::class, 'destroy'])->name('destroy');
    Route::get('/{savedView}/conversation', [SavedViewController::class, 'conversation'])->name('conversation');
    Route::post('/{savedView}/set-default', [SavedViewController::class, 'setDefault'])->name('set-default');
    Route::post('/{savedView}/duplicate', [SavedViewController::class, 'duplicate'])->name('duplicate');
    Route::post('/reorder', [SavedViewController::class, 'reorder'])->name('reorder');
});

// API Routes
Route::prefix('api')->name('api.')->group(function () {
    Route::get('/contacts/search', [ConversationController::class, 'searchContacts'])->name('contacts.search');
    Route::get('/conversation/search', [ConversationController::class, 'search'])->name('conversation.search');
    Route::post('/canneds/{cannedResponse}/use', [CannedController::class, 'recordUsage'])->name('canneds.use');
});

// Contacts (Additional Routes)
Route::get('contacts/import', [ContactController::class, 'importForm'])->name('contacts.import-form');
Route::post('contacts/merge-form', [ContactController::class, 'mergeForm'])->name('contacts.merge-form');
Route::post('contacts/merge', [ContactController::class, 'merge'])->name('contacts.merge');
Route::post('contacts/{contact}/block', [ContactController::class, 'block'])->name('contacts.block');
Route::post('contacts/{contact}/unblock', [ContactController::class, 'unblock'])->name('contacts.unblock');
Route::post('contacts/{contact}/labels', [ContactController::class, 'addLabel'])->name('contacts.add-label');
Route::delete('contacts/{contact}/labels/{label}', [ContactController::class, 'removeLabel'])->name('contacts.remove-label');
Route::post('contacts/{contact}/notes', [ContactController::class, 'storeNote'])->name('contacts.store-note');
Route::delete('contacts/{contact}/notes/{note}', [ContactController::class, 'destroyNote'])->name('contacts.destroy-note');
Route::put('contacts/{contact}/custom-attributes', [ContactController::class, 'updateCustomAttributes'])->name('contacts.update-custom-attributes');
Route::post('contacts/{contact}/avatar', [ContactAvatarController::class, 'upload'])->name('contacts.upload-avatar');
Route::delete('contacts/{contact}/avatar', [ContactAvatarController::class, 'destroy'])->name('contacts.delete-avatar');

// Contact Merge
Route::prefix('contacts/merge')->name('contacts.merge.')->group(function () {
    Route::get('/', [ContactMergeController::class, 'index'])->name('index');
    Route::post('/find-duplicates', [ContactMergeController::class, 'findDuplicates'])->name('find-duplicates');
    Route::post('/preview', [ContactMergeController::class, 'previewMerge'])->name('preview');
    Route::post('/execute', [ContactMergeController::class, 'executeMerge'])->name('execute');
});

// Contact Filters
Route::prefix('contacts')->name('contacts.')->group(function () {
    Route::get('filters', [ContactFilterController::class, 'index'])->name('filters.index');
    Route::post('filters', [ContactFilterController::class, 'store'])->name('filters.store');
    Route::get('filters/metadata', [ContactFilterController::class, 'metadata'])->name('filters.metadata');
    Route::get('filters/{filter}/apply', [ContactFilterController::class, 'apply'])->name('filters.apply');
    Route::patch('filters/{filter}', [ContactFilterController::class, 'update'])->name('filters.update');
    Route::delete('filters/{filter}', [ContactFilterController::class, 'destroy'])->name('filters.destroy');

    // Bulk Actions
    Route::post('bulk/add-labels', [BulkContactController::class, 'addLabels'])->name('bulk.add-labels');
    Route::post('bulk/remove-labels', [BulkContactController::class, 'removeLabels'])->name('bulk.remove-labels');
    Route::post('bulk/delete', [BulkContactController::class, 'delete'])->name('bulk.delete');
    Route::post('bulk/export', [BulkContactController::class, 'export'])->name('bulk.export');

    // Segments
    Route::post('segments/{segment}/refresh', [ContactSegmentController::class, 'refresh'])->name('segments.refresh');
    Route::post('segments/{segment}/add-contacts', [ContactSegmentController::class, 'addContacts'])->name('segments.add-contacts');
    Route::post('segments/{segment}/remove-contacts', [ContactSegmentController::class, 'removeContacts'])->name('segments.remove-contacts');
});

// Contact Segments
Route::resource('contacts/segments', ContactSegmentController::class)->names([
    'index' => 'contacts.segments.index',
    'create' => 'contacts.segments.create',
    'store' => 'contacts.segments.store',
    'show' => 'contacts.segments.show',
    'edit' => 'contacts.segments.edit',
    'update' => 'contacts.segments.update',
    'destroy' => 'contacts.segments.destroy',
]);

// Teams
Route::resource('teams', TeamController::class);

// Team Roles & Permissions
Route::resource('team-roles', TeamRoleController::class);

// Agent Status & Availability
Route::prefix('agent')->name('agent.')->group(function () {
    Route::get('status', [AgentStatusController::class, 'show'])->name('status.show');
    Route::post('status', [AgentStatusController::class, 'update'])->name('status.update');
    Route::post('heartbeat', [AgentStatusController::class, 'heartbeat'])->name('heartbeat');
    Route::get('agents', [AgentStatusController::class, 'index'])->name('agents.index');
    Route::get('available', [AgentStatusController::class, 'available'])->name('agents.available');
});

// Labels
Route::resource('labels', LabelController::class);

// Custom Attributes
Route::resource('custom-attributes', CustomAttributeDefinitionController::class);

// Canned Responses
Route::get('/canneds/search', [CannedController::class, 'search'])->name('canneds.search');
Route::resource('canneds', CannedController::class);

// ConversationMessage Templates
Route::get('/message-templates/search', [MessageTemplateController::class, 'search'])->name('message-templates.search');
Route::get('/message-templates/{messageTemplate}/preview', [MessageTemplateController::class, 'preview'])->name('message-templates.preview');
Route::post('/message-templates/{messageTemplate}/render', [MessageTemplateController::class, 'render'])->name('message-templates.render');
Route::resource('message-templates', MessageTemplateController::class);

// Email Templates
Route::get('/email-templates/{emailTemplate}/preview', [EmailTemplateController::class, 'preview'])->name('email-templates.preview');
Route::get('/email-templates/variables', [EmailTemplateController::class, 'getVariables'])->name('email-templates.getVariables');
Route::post('/email-templates/{emailTemplate}/toggle', [EmailTemplateController::class, 'toggleActive'])->name('email-templates.toggle');
Route::resource('email-templates', EmailTemplateController::class);

// Automation Rules
Route::post('/automation-rules/{automationRule}/toggle', [AutomationController::class, 'toggleActive'])->name('automation-rules.toggle');
Route::resource('automation-rules', AutomationController::class);

// Macros
Route::post('/macros/{macro}/execute', [MacroController::class, 'execute'])->name('macros.execute');
Route::resource('macros', MacroController::class);

// Webhooks
Route::resource('webhooks', WebhookController::class);

// Integrations Hooks
Route::post('/integrations/{integrationsHook}/toggle', [IntegrationController::class, 'toggleStatus'])->name('integrations.toggle');
Route::resource('integrations', IntegrationController::class);

// Settings
Route::prefix('settings')->name('settings.')->group(function () {
    // Channel Credentials
    Route::get('/channel-credentials', [ChannelCredentialsController::class, 'index'])->name('channel-credentials.index');
    Route::get('/channel-credentials/{channel}', [ChannelCredentialsController::class, 'show'])->name('channel-credentials.show');
    Route::get('/channel-credentials/{channel}/check', [ChannelCredentialsController::class, 'checkConfiguration'])->name('channel-credentials.check');

    // Business Hours
    Route::get('/hours', [HourController::class, 'index'])->name('hours.index');
    Route::put('/hours', [HourController::class, 'update'])->name('hours.update');
    Route::post('/hours/reset', [HourController::class, 'reset'])->name('hours.reset');

    // Notification Settings
    Route::get('/notifications', [NotificationSettingsController::class, 'index'])->name('notifications.index');
    Route::put('/notifications', [NotificationSettingsController::class, 'update'])->name('notifications.update');
});

// SLA Policies
Route::resource('sla-policies', SlaPolicyController::class);

// Reports
Route::prefix('reports')->name('reports.')->group(function () {
    Route::get('/sla', [SlaReportController::class, 'index'])->name('sla.index');
    Route::get('/sla/widget', [SlaReportController::class, 'widget'])->name('sla.widget');
    Route::get('/csat', [CsatReportController::class, 'index'])->name('csat.index');
    Route::get('/contacts', [ContactReportController::class, 'index'])->name('contacts.index');
    Route::post('/contacts/export', [ContactReportController::class, 'export'])->name('contacts.export');

    // Agent Performance Reports
    Route::prefix('agent-performance')->name('agent-performance.')->group(function () {
        Route::get('/', [AgentPerformanceReportController::class, 'index'])->name('index');
        Route::get('/overview', [AgentPerformanceReportController::class, 'overview'])->name('overview');
        Route::get('/my-performance', [AgentPerformanceReportController::class, 'myPerformance'])->name('my-performance');
        Route::get('/agents/{user}/metrics', [AgentPerformanceReportController::class, 'agentMetrics'])->name('agents.metrics');
        Route::get('/agents/{user}/trends', [AgentPerformanceReportController::class, 'trends'])->name('agents.trends');
        Route::get('/agents/{user}/export', [AgentPerformanceReportController::class, 'export'])->name('agents.export');
        Route::get('/leaderboard', [AgentPerformanceReportController::class, 'leaderboard'])->name('leaderboard');
        Route::get('/teams/{team}/comparison', [AgentPerformanceReportController::class, 'teamComparison'])->name('teams.comparison');
        Route::get('/compare', [AgentPerformanceReportController::class, 'compareAgents'])->name('compare');
    });

    // Analytics Dashboard
    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/', [AnalyticsDashboardController::class, 'index'])->name('index');
        Route::get('/export', [AnalyticsDashboardController::class, 'export'])->name('export');
    });

    // Conversations Routing Stats
    Route::get('/routing-stats', [ConversationRoutingController::class, 'stats'])->name('routing-stats');

    // Team Workload Balancing
    Route::prefix('team-workload')->name('team-workload.')->group(function () {
        Route::get('/', [TeamWorkloadBalancingController::class, 'index'])->name('index');
        Route::get('/recommendations', [TeamWorkloadBalancingController::class, 'recommendations'])->name('recommendations');
        Route::get('/{team}/agents', [TeamWorkloadBalancingController::class, 'agents'])->name('agents');
        Route::post('/{team}/rebalance', [TeamWorkloadBalancingController::class, 'rebalance'])->name('rebalance');
        Route::get('/{team}/imbalance-score', [TeamWorkloadBalancingController::class, 'imbalanceScore'])->name('imbalance-score');
    });
});

// Audit Logs
Route::get('audits', [AuditLogController::class, 'index'])->name('audits.index');

// Groups Routes
// Note: No prefix/name needed here - RouteServiceProvider already adds 'admin/helpdesk' prefix and 'admin.' name
// Main Groups Index (this was duplicated, commenting out)
// Route::get('/', [HelpdeskCustomersController::class, 'index'])->name('index');

// Customers
Route::get('/customers', [HelpdeskCustomersController::class, 'index'])->name('customers.index');
Route::get('/customers/create', [HelpdeskCustomersController::class, 'create'])->name('customers.create');
Route::post('/customers', [HelpdeskCustomersController::class, 'store'])->name('customers.store');
Route::get('/customers/{customer}', [HelpdeskCustomersController::class, 'show'])->name('customers.show');
Route::get('/customers/{customer}/edit', [HelpdeskCustomersController::class, 'edit'])->name('customers.edit');
Route::put('/customers/{customer}', [HelpdeskCustomersController::class, 'update'])->name('customers.update');
Route::delete('/customers/{customer}', [HelpdeskCustomersController::class, 'destroy'])->name('customers.destroy');
Route::post('/customers/{customer}/restore', [HelpdeskCustomersController::class, 'restore'])->name('customers.restore');
Route::delete('/customers/{customer}/force-delete', [HelpdeskCustomersController::class, 'forceDelete'])->name('customers.forceDelete');
Route::post('/customers/{customer}/ban', [HelpdeskCustomersController::class, 'ban'])->name('customers.ban');
Route::post('/customers/{customer}/unban', [HelpdeskCustomersController::class, 'unban'])->name('customers.unban');

// Conversations
Route::get('/conversation', [HelpdeskConversationsController::class, 'index'])->name('conversation.index');
Route::get('/conversation/create', [HelpdeskConversationsController::class, 'create'])->name('conversation.create');
Route::post('/conversation', [HelpdeskConversationsController::class, 'store'])->name('conversation.store');
Route::get('/conversation/{conversation}', [HelpdeskConversationsController::class, 'show'])->name('conversation.show');
Route::get('/conversation/{conversation}/edit', [HelpdeskConversationsController::class, 'edit'])->name('conversation.edit');
Route::put('/conversation/{conversation}', [HelpdeskConversationsController::class, 'update'])->name('conversation.update');
Route::delete('/conversation/{conversation}', [HelpdeskConversationsController::class, 'destroy'])->name('conversation.destroy');
Route::post('/conversation/{conversation}/restore', [HelpdeskConversationsController::class, 'restore'])->name('conversation.restore');
Route::delete('/conversation/{conversation}/force-delete', [HelpdeskConversationsController::class, 'forceDelete'])->name('conversation.forceDelete');
Route::post('/conversation/{conversation}/close', [HelpdeskConversationsController::class, 'close'])->name('conversation.close');
Route::post('/conversation/{conversation}/reopen', [HelpdeskConversationsController::class, 'reopen'])->name('conversation.reopen');
Route::post('/conversation/{conversation}/archive', [HelpdeskConversationsController::class, 'archive'])->name('conversation.archive');
Route::post('/conversation/{conversation}/unarchive', [HelpdeskConversationsController::class, 'unarchive'])->name('conversation.unarchive');
Route::post('/conversation/{conversation}/messages', [HelpdeskConversationsController::class, 'storeMessage'])->name('conversation.messages.store');

// Settings
Route::prefix('settings')->name('settings.')->group(function () {
    // General Settings
    Route::get('/', [SettingsHelpdeskController::class, 'index'])->name('index');
    Route::put('/', [SettingsHelpdeskController::class, 'update'])->name('update');

    // Teams
    Route::resource('teams', TeamController::class);
});
