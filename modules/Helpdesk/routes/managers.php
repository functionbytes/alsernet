<?php

use Illuminate\Support\Facades\Route;
use Modules\Helpdesk\Http\Controllers\Managers\AgentsController;
use Modules\Helpdesk\Http\Controllers\Managers\AiAgentFlowsController;
use Modules\Helpdesk\Http\Controllers\Managers\AiAgentSettingsController;
use Modules\Helpdesk\Http\Controllers\Managers\BulkConversationsController;
use Modules\Helpdesk\Http\Controllers\Managers\BulkTicketsController;
use Modules\Helpdesk\Http\Controllers\Managers\CampaignsController as HelpdeskCampaignsController;
use Modules\Helpdesk\Http\Controllers\Managers\ConversationsController as HelpdeskConversationsController;
use Modules\Helpdesk\Http\Controllers\Managers\CustomersController as HelpdeskCustomersController;
use Modules\Helpdesk\Http\Controllers\Managers\DashboardController;
use Modules\Helpdesk\Http\Controllers\Managers\HelpCenterController;
use Modules\Helpdesk\Http\Controllers\Managers\RecurringTicketsController;
use Modules\Helpdesk\Http\Controllers\Managers\ReportsController;
use Modules\Helpdesk\Http\Controllers\Managers\Settings\AttributesController;
use Modules\Helpdesk\Http\Controllers\Managers\Settings\SettingsController;
use Modules\Helpdesk\Http\Controllers\Managers\Settings\StatusesController;
use Modules\Helpdesk\Http\Controllers\Managers\Settings\TagsController;
use Modules\Helpdesk\Http\Controllers\Managers\Settings\TeamController;
use Modules\Helpdesk\Http\Controllers\Managers\Settings\TicketCannedRepliesController;
use Modules\Helpdesk\Http\Controllers\Managers\Settings\TicketCategoriesController;
use Modules\Helpdesk\Http\Controllers\Managers\Settings\TicketGroupsController;
use Modules\Helpdesk\Http\Controllers\Managers\Settings\TicketSlaPoliciesController;
use Modules\Helpdesk\Http\Controllers\Managers\Settings\TicketStatusesController;
use Modules\Helpdesk\Http\Controllers\Managers\Settings\TicketViewsController;
use Modules\Helpdesk\Http\Controllers\Managers\SocialIntegrationsController;
use Modules\Helpdesk\Http\Controllers\Managers\TicketCommentsController;
use Modules\Helpdesk\Http\Controllers\Managers\TicketNotesController;
use Modules\Helpdesk\Http\Controllers\Managers\TicketsController as HelpdeskTicketsController;
use Modules\Helpdesk\Http\Controllers\Managers\TicketTemplatesController;
use Modules\Helpdesk\Http\Controllers\Managers\TimeEntriesController;

Route::group(['prefix' => ''], function () {

    // Main Helpdesk Index
    Route::get('/', [DashboardController::class, 'index'])->name('manager.helpdesk');

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

    // Conversations bulk
    Route::post('/conversations/bulk', [BulkConversationsController::class, 'handle'])->name('manager.helpdesk.conversations.bulk');

    // Conversations
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

    // Tickets
    Route::post('/tickets/bulk', [BulkTicketsController::class, 'handle'])->name('manager.helpdesk.tickets.bulk');
    Route::get('/tickets', [HelpdeskTicketsController::class, 'index'])->name('manager.helpdesk.tickets.index');
    Route::get('/tickets/create', [HelpdeskTicketsController::class, 'create'])->name('manager.helpdesk.tickets.create');
    Route::post('/tickets', [HelpdeskTicketsController::class, 'store'])->name('manager.helpdesk.tickets.store');
    Route::get('/tickets/{ticket}', [HelpdeskTicketsController::class, 'show'])->name('manager.helpdesk.tickets.show');
    Route::get('/tickets/{ticket}/edit', [HelpdeskTicketsController::class, 'edit'])->name('manager.helpdesk.tickets.edit');
    Route::put('/tickets/{ticket}', [HelpdeskTicketsController::class, 'update'])->name('manager.helpdesk.tickets.update');
    Route::delete('/tickets/{ticket}', [HelpdeskTicketsController::class, 'destroy'])->name('manager.helpdesk.tickets.destroy');
    Route::post('/tickets/{ticket}/restore', [HelpdeskTicketsController::class, 'restore'])->name('manager.helpdesk.tickets.restore')->withTrashed();
    Route::delete('/tickets/{ticket}/force-delete', [HelpdeskTicketsController::class, 'forceDelete'])->name('manager.helpdesk.tickets.force-delete')->withTrashed();
    Route::post('/tickets/{ticket}/close', [HelpdeskTicketsController::class, 'close'])->name('manager.helpdesk.tickets.close');
    Route::post('/tickets/{ticket}/resolve', [HelpdeskTicketsController::class, 'resolve'])->name('manager.helpdesk.tickets.resolve');
    Route::post('/tickets/{ticket}/reopen', [HelpdeskTicketsController::class, 'reopen'])->name('manager.helpdesk.tickets.reopen');
    Route::post('/tickets/{ticket}/archive', [HelpdeskTicketsController::class, 'archive'])->name('manager.helpdesk.tickets.archive');
    Route::post('/tickets/{ticket}/unarchive', [HelpdeskTicketsController::class, 'unarchive'])->name('manager.helpdesk.tickets.unarchive');
    Route::post('/tickets/{ticket}/messages', [HelpdeskTicketsController::class, 'storeMessage'])->name('manager.helpdesk.tickets.messages.store');
    Route::post('/tickets/{ticket}/merge', [HelpdeskTicketsController::class, 'merge'])->name('manager.helpdesk.tickets.merge');
    Route::post('/tickets/{ticket}/watch', [HelpdeskTicketsController::class, 'watch'])->name('manager.helpdesk.tickets.watch');
    Route::delete('/tickets/{ticket}/watch', [HelpdeskTicketsController::class, 'unwatch'])->name('manager.helpdesk.tickets.unwatch');
    Route::post('/tickets/{ticket}/sla/pause', [HelpdeskTicketsController::class, 'pauseSla'])->name('manager.helpdesk.tickets.sla.pause');
    Route::post('/tickets/{ticket}/sla/resume', [HelpdeskTicketsController::class, 'resumeSla'])->name('manager.helpdesk.tickets.sla.resume');

    // Ticket Time Entries
    Route::get('/tickets/{ticket}/time-entries', [TimeEntriesController::class, 'index'])->name('manager.helpdesk.tickets.time-entries.index');
    Route::post('/tickets/{ticket}/time-entries', [TimeEntriesController::class, 'store'])->name('manager.helpdesk.tickets.time-entries.store');
    Route::delete('/tickets/{ticket}/time-entries/{timeEntry}', [TimeEntriesController::class, 'destroy'])->name('manager.helpdesk.tickets.time-entries.destroy');

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

    // Campaigns
    Route::get('/campaigns', [HelpdeskCampaignsController::class, 'index'])->name('manager.helpdesk.campaigns.index');
    Route::get('/campaigns/templates', [HelpdeskCampaignsController::class, 'templates'])->name('manager.helpdesk.campaigns.templates');
    Route::get('/campaigns/create', [HelpdeskCampaignsController::class, 'create'])->name('manager.helpdesk.campaigns.create');
    Route::post('/campaigns', [HelpdeskCampaignsController::class, 'store'])->name('manager.helpdesk.campaigns.store');
    Route::get('/campaigns/{campaign}', [HelpdeskCampaignsController::class, 'show'])->name('manager.helpdesk.campaigns.show');
    Route::get('/campaigns/{campaign}/edit', [HelpdeskCampaignsController::class, 'edit'])->name('manager.helpdesk.campaigns.edit');
    Route::put('/campaigns/{campaign}', [HelpdeskCampaignsController::class, 'update'])->name('manager.helpdesk.campaigns.update');
    Route::delete('/campaigns/{campaign}', [HelpdeskCampaignsController::class, 'destroy'])->name('manager.helpdesk.campaigns.destroy');
    Route::post('/campaigns/{campaign}/publish', [HelpdeskCampaignsController::class, 'publish'])->name('manager.helpdesk.campaigns.publish');
    Route::post('/campaigns/{campaign}/pause', [HelpdeskCampaignsController::class, 'pause'])->name('manager.helpdesk.campaigns.pause');
    Route::post('/campaigns/{campaign}/resume', [HelpdeskCampaignsController::class, 'resume'])->name('manager.helpdesk.campaigns.resume');
    Route::post('/campaigns/{campaign}/end', [HelpdeskCampaignsController::class, 'end'])->name('manager.helpdesk.campaigns.end');
    Route::get('/campaigns/{campaign}/statistics', [HelpdeskCampaignsController::class, 'statistics'])->name('manager.helpdesk.campaigns.statistics');
    Route::post('/campaigns/{campaign}/duplicate', [HelpdeskCampaignsController::class, 'duplicate'])->name('manager.helpdesk.campaigns.duplicate');

    // AI Agent
    Route::prefix('ai')->group(function () {
        // Settings
        Route::get('backups', [AiAgentSettingsController::class, 'index'])->name('manager.helpdesk.ai.backups');
        Route::put('backups', [AiAgentSettingsController::class, 'update'])->name('manager.helpdesk.ai.backups.update');
        Route::post('backups/test-connection', [AiAgentSettingsController::class, 'testConnection'])->name('manager.helpdesk.ai.backups.test');
        Route::post('backups/get-models', [AiAgentSettingsController::class, 'getModels'])->name('manager.helpdesk.ai.backups.get-models');
        Route::get('backups/statistics', [AiAgentSettingsController::class, 'statistics'])->name('manager.helpdesk.ai.backups.statistics');

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
    Route::prefix('backups')->name('manager.helpdesk.backups.')->group(function () {
        // Tickets Settings
        Route::get('tickets', [SettingsController::class, 'ticketsIndex'])->name('tickets');
        Route::put('tickets', [SettingsController::class, 'ticketsUpdate'])->name('tickets.update');

        // LiveChat Settings (New Helpdesk Widget)
        Route::get('livechat', [SettingsController::class, 'livechatIndex'])->name('livechat');
        Route::put('livechat', [SettingsController::class, 'livechatUpdate'])->name('livechat.update');

        // AI Settings
        Route::get('ai', [SettingsController::class, 'aiIndex'])->name('ai');
        Route::put('ai', [SettingsController::class, 'aiUpdate'])->name('ai.update');

        // Uploading Settings
        Route::get('uploading', [SettingsController::class, 'uploadingIndex'])->name('uploading');
        Route::put('uploading', [SettingsController::class, 'uploadingUpdate'])->name('uploading.update');

        // Social Integrations Settings
        Route::get('social-integrations', [SocialIntegrationsController::class, 'index'])->name('social-integrations.index');
        Route::post('social-integrations/test/whatsapp', [SocialIntegrationsController::class, 'testWhatsapp'])->name('social-integrations.test.whatsapp');
        Route::post('social-integrations/test/facebook', [SocialIntegrationsController::class, 'testFacebook'])->name('social-integrations.test.facebook');
        Route::post('social-integrations/test/instagram', [SocialIntegrationsController::class, 'testInstagram'])->name('social-integrations.test.instagram');

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

            // Conversation Statuses Settings
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

    // Ticket Templates
    Route::resource('ticket-templates', TicketTemplatesController::class)->names([
        'index' => 'ticket-templates.index',
        'create' => 'ticket-templates.create',
        'store' => 'ticket-templates.store',
        'show' => 'ticket-templates.show',
        'edit' => 'ticket-templates.edit',
        'update' => 'ticket-templates.update',
        'destroy' => 'ticket-templates.destroy',
    ]);

    // Recurring Tickets
    Route::resource('recurring-tickets', RecurringTicketsController::class)->names([
        'index' => 'recurring-tickets.index',
        'create' => 'recurring-tickets.create',
        'store' => 'recurring-tickets.store',
        'edit' => 'recurring-tickets.edit',
        'update' => 'recurring-tickets.update',
        'destroy' => 'recurring-tickets.destroy',
    ])->except(['show']);
    Route::post('recurring-tickets/{recurringTicket}/toggle', [RecurringTicketsController::class, 'toggle'])->name('recurring-tickets.toggle');

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
        Route::get('/export', [ReportsController::class, 'export'])->name('export');
        Route::get('/agents', [ReportsController::class, 'agentPerformance'])->name('agents');
        Route::get('/trend', [ReportsController::class, 'trend'])->name('trend');
    });

    // Help Center Manager
    Route::prefix('helpcenter')->group(function () {
        // Main Index
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
});
