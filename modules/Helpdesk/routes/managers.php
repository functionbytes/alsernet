<?php

use Illuminate\Support\Facades\Route;
use Modules\Helpdesk\Http\Controllers\Api\TagsAutocompleteController;
use Modules\Helpdesk\Http\Controllers\Managers\AgentsController;
use Modules\Helpdesk\Http\Controllers\Managers\BulkConversationsController;
use Modules\Helpdesk\Http\Controllers\Managers\ConversationsController as HelpdeskConversationsController;
use Modules\Helpdesk\Http\Controllers\Managers\CustomersController as HelpdeskCustomersController;
use Modules\Helpdesk\Http\Controllers\Managers\DashboardController;
use Modules\Helpdesk\Http\Controllers\Managers\HelpCenterController;
use Modules\Helpdesk\Http\Controllers\Managers\ReportsController;
use Modules\Helpdesk\Http\Controllers\Managers\Settings\AttributesController;
use Modules\Helpdesk\Http\Controllers\Managers\Settings\ScheduleController;
use Modules\Helpdesk\Http\Controllers\Managers\Settings\SettingsController;
use Modules\Helpdesk\Http\Controllers\Managers\Settings\StatusesController;
use Modules\Helpdesk\Http\Controllers\Managers\Settings\TagsController;
use Modules\Helpdesk\Http\Controllers\Managers\Settings\TeamController;
use Modules\Helpdesk\Http\Controllers\Managers\Settings\WebhooksController;
use Modules\Helpdesk\Http\Controllers\Managers\SocialIntegrationsController;

Route::group(['prefix' => ''], function () {

    // Tags autocomplete (web, throttled, authenticated)
    Route::get('/api/tags-autocomplete', TagsAutocompleteController::class)
        ->middleware('throttle:60,1')
        ->name('manager.helpdesk.api.tags.autocomplete');

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

    // Settings
    Route::prefix('settings')->name('manager.helpdesk.settings.')->group(function () {
        // LiveChat Settings
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

        // Customers Settings
        Route::get('customers', [HelpdeskCustomersController::class, 'index'])->name('customers');

        // Outbound Webhooks
        Route::prefix('webhooks')->name('webhooks.')->group(function () {
            Route::get('/', [WebhooksController::class, 'index'])->name('index');
            Route::get('create', [WebhooksController::class, 'create'])->name('create');
            Route::post('/', [WebhooksController::class, 'store'])->name('store');
            Route::get('{webhook}/edit', [WebhooksController::class, 'edit'])->name('edit');
            Route::put('{webhook}', [WebhooksController::class, 'update'])->name('update');
            Route::delete('{webhook}', [WebhooksController::class, 'destroy'])->name('destroy');
        });

        // Schedule management (shifts, vacations, on-call)
        Route::prefix('schedule')->name('schedule.')->group(function () {
            Route::get('/', [ScheduleController::class, 'index'])->name('index');
            Route::post('shifts', [ScheduleController::class, 'storeShift'])->name('shifts.store');
            Route::delete('shifts/{shift}', [ScheduleController::class, 'destroyShift'])->name('shifts.destroy');
            Route::post('vacations', [ScheduleController::class, 'storeVacation'])->name('vacations.store');
            Route::delete('vacations/{vacation}', [ScheduleController::class, 'destroyVacation'])->name('vacations.destroy');
            Route::post('oncall', [ScheduleController::class, 'storeOncall'])->name('oncall.store');
            Route::delete('oncall/{oncall}', [ScheduleController::class, 'destroyOncall'])->name('oncall.destroy');
        });

        Route::prefix('tickets')->name('tickets.')->group(function () {
            // Team Settings
            Route::prefix('team')->name('team.')->group(function () {
                Route::get('members', [TeamController::class, 'membersIndex'])->name('members');
                Route::get('members/{id}/edit', [TeamController::class, 'memberEdit'])->name('member.edit');
                Route::put('members/{id}', [TeamController::class, 'memberUpdate'])->name('member.update');

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
});
