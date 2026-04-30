<?php

use Illuminate\Support\Facades\Route;
use Modules\Chat\Http\Controllers\Api\WidgetApiController;
use Modules\Chat\Http\Controllers\Api\WidgetConversationController;
use Modules\Chat\Http\Controllers\Helpdesk\Conversations\ConversationAssignmentController;
use Modules\Chat\Http\Controllers\Helpdesk\Conversations\ConversationController;
use Modules\Chat\Http\Controllers\Helpdesk\Conversations\ConversationExportController;
use Modules\Chat\Http\Controllers\Helpdesk\Conversations\ConversationLabelController;
use Modules\Chat\Http\Controllers\Helpdesk\Conversations\ConversationMacroController;
use Modules\Chat\Http\Controllers\Helpdesk\Conversations\ConversationRoutingController;
use Modules\Chat\Http\Controllers\Helpdesk\Conversations\ConversationStatusController;
use Modules\Chat\Http\Controllers\Helpdesk\Conversations\MessageController;
use Modules\Chat\Http\Controllers\Helpdesk\Conversations\TypingIndicatorController;
use Modules\Chat\Http\Controllers\Helpdesk\Customers\CustomAttributeDefinitionController;
use Modules\Chat\Http\Controllers\Helpdesk\Customers\CustomerController;
use Modules\Chat\Http\Controllers\Helpdesk\Customers\CustomerImportController;
use Modules\Chat\Http\Controllers\Helpdesk\Customers\CustomerMergeController;
use Modules\Chat\Http\Controllers\Helpdesk\Customers\CustomerSegmentController;
use Modules\Chat\Http\Controllers\Helpdesk\Helpcenters\HelpcenterController;
use Modules\Chat\Http\Controllers\Helpdesk\Reports\AgentPerformanceReportController;
use Modules\Chat\Http\Controllers\Helpdesk\Reports\AgentStatusController;
use Modules\Chat\Http\Controllers\Helpdesk\Reports\AnalyticsDashboardController;
use Modules\Chat\Http\Controllers\Helpdesk\Reports\CsatReportController;
use Modules\Chat\Http\Controllers\Helpdesk\Reports\CustomerReportController;
use Modules\Chat\Http\Controllers\Helpdesk\Reports\SlaReportController;
use Modules\Chat\Http\Controllers\Helpdesk\Search\SearchController;
use Modules\Chat\Http\Controllers\Pages\DemoController;
use Modules\Chat\Http\Controllers\Pages\WidgetController;
use Modules\Chat\Http\Controllers\Pages\WidgetScriptController;
use Modules\Chat\Http\Controllers\Settings\AiSettingsController;
use Modules\Chat\Http\Controllers\Settings\AttachmentSettingsController;
use Modules\Chat\Http\Controllers\Settings\AuditLogController;
use Modules\Chat\Http\Controllers\Settings\AutomationController;
use Modules\Chat\Http\Controllers\Settings\Channels\ApiController;
use Modules\Chat\Http\Controllers\Settings\Channels\ChannelCredentialsController;
use Modules\Chat\Http\Controllers\Settings\Channels\EmailController;
use Modules\Chat\Http\Controllers\Settings\Channels\FacebookController;
use Modules\Chat\Http\Controllers\Settings\Channels\InstagramController;
use Modules\Chat\Http\Controllers\Settings\Channels\SmsController;
use Modules\Chat\Http\Controllers\Settings\Channels\WebController;
use Modules\Chat\Http\Controllers\Settings\Channels\WhatsappController;
use Modules\Chat\Http\Controllers\Settings\Channels\WhatsappWebhookLogController;
use Modules\Chat\Http\Controllers\Settings\ConfigurationController;
use Modules\Chat\Http\Controllers\Settings\EmailTemplateController;
use Modules\Chat\Http\Controllers\Settings\HourController;
use Modules\Chat\Http\Controllers\Settings\IntegrationController;
use Modules\Chat\Http\Controllers\Settings\LabelController;
use Modules\Chat\Http\Controllers\Settings\LivechatSettingsController;
use Modules\Chat\Http\Controllers\Settings\MacroController;
use Modules\Chat\Http\Controllers\Settings\MessageTemplateController;
use Modules\Chat\Http\Controllers\Settings\NotificationSettingsController;
use Modules\Chat\Http\Controllers\Settings\Reports\ApiUsageDashboardController;
use Modules\Chat\Http\Controllers\Settings\SavedViewController;
use Modules\Chat\Http\Controllers\Settings\SlaPolicyController;
use Modules\Chat\Http\Controllers\Settings\Team\TeamController;
use Modules\Chat\Http\Controllers\Settings\Team\TeamRoleController;
use Modules\Chat\Http\Controllers\Settings\Team\TeamWorkloadBalancingController;
use Modules\Chat\Http\Controllers\Settings\TicketsSettingsController;
use Modules\Chat\Http\Controllers\Settings\UploadSettingsController;
use Modules\Chat\Http\Controllers\Settings\WebhookController;

Route::prefix('setting/chat')->name('settings.chat.')
    ->middleware(['web', 'auth', 'role:super-settings', 'throttle:chat.settings'])
    ->group(function () {

        Route::get('configurations/global', [ConfigurationController::class, 'globalConfig'])->name('configurations.global');
        Route::put('configurations/global', [ConfigurationController::class, 'updateGlobalConfig'])->name('configurations.update');

        Route::get('business-hours', [ConfigurationController::class, 'businessHours'])->name('business-hours');
        Route::put('business-hours', [ConfigurationController::class, 'updateBusinessHours'])->name('business-hours.update');

        // Business Hours (database-driven)
        Route::prefix('hours')->name('hours.')->group(function () {
            Route::get('/', [HourController::class, 'index'])->name('index');
            Route::put('/', [HourController::class, 'update'])->name('update');
            Route::post('reset', [HourController::class, 'reset'])->name('reset');
        });

        Route::get('notifications', [NotificationSettingsController::class, 'index'])->name('notifications');
        Route::put('notifications', [NotificationSettingsController::class, 'update'])->name('notifications.update');

        Route::get('tickets', [TicketsSettingsController::class, 'index'])->name('tickets');
        Route::put('tickets', [TicketsSettingsController::class, 'update'])->name('tickets.update');

        Route::get('livechat', [LivechatSettingsController::class, 'index'])->name('livechat');
        Route::put('livechat', [LivechatSettingsController::class, 'update'])->name('livechat.update');

        Route::get('ai', [AiSettingsController::class, 'index'])->name('ai');
        Route::put('ai', [AiSettingsController::class, 'update'])->name('ai.update');

        Route::get('uploading', [UploadSettingsController::class, 'index'])->name('uploading');
        Route::put('uploading', [UploadSettingsController::class, 'update'])->name('uploading.update');

        Route::get('attachments', [AttachmentSettingsController::class, 'index'])->name('attachments');
        Route::put('attachments', [AttachmentSettingsController::class, 'update'])->name('attachments.update');
        Route::get('attachments/disk-stats/{disk}', [AttachmentSettingsController::class, 'diskStats'])->name('attachments.disk-stats');
        Route::get('attachments/history', [AttachmentSettingsController::class, 'history'])->name('attachments.history');

        // Customer export/import — import is rate-limited more strictly
        Route::get('customers/export', [CustomerController::class, 'export'])->name('customers.export');
        Route::post('customers/import', [CustomerController::class, 'import'])
            ->middleware('throttle:chat.imports')
            ->name('customers.import');
        Route::resource('customers', CustomerController::class);

        // Teams — sensitive: creating/deleting teams affects role assignments
        Route::resource('teams', TeamController::class);

        // Team Roles — sensitive: directly controls permissions
        Route::middleware('throttle:chat.sensitive')->group(function () {
            Route::resource('team-roles', TeamRoleController::class);
        });

        // Agent Status
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

        // Message Templates
        Route::get('/message/search', [MessageTemplateController::class, 'search'])->name('message.search');
        Route::get('/message/{messageTemplate}/preview', [MessageTemplateController::class, 'preview'])->name('message.preview');
        Route::post('/message/{messageTemplate}/render', [MessageTemplateController::class, 'render'])->name('message.render');
        Route::resource('message', MessageTemplateController::class, ['parameters' => ['message' => 'messageTemplate']]);

        // Email Templates
        Route::get('/email/{emailTemplate}/preview', [EmailTemplateController::class, 'preview'])->name('email.preview');
        Route::get('/email/variables', [EmailTemplateController::class, 'getVariables'])->name('email.getVariables');
        Route::post('/email/{emailTemplate}/toggle', [EmailTemplateController::class, 'toggleActive'])->name('email.toggle');
        Route::resource('email', EmailTemplateController::class, ['parameters' => ['email' => 'emailTemplate']]);

        // Automation Rules
        Route::post('/automation-rules/{automationRule}/toggle', [AutomationController::class, 'toggleActive'])->name('automation-rules.toggle');
        Route::resource('automation-rules', AutomationController::class);

        // Macros — execute is sensitive (triggers server-side actions)
        Route::post('/macros/{macro}/execute', [MacroController::class, 'execute'])
            ->middleware('throttle:chat.sensitive')
            ->name('macros.execute');
        Route::resource('macros', MacroController::class);

        // Webhooks
        Route::middleware('throttle:chat.webhooks')->group(function () {
            Route::resource('webhooks', WebhookController::class);
        });

        // Integrations
        Route::post('/integrations/{integrationsHook}/toggle', [IntegrationController::class, 'toggleStatus'])->name('integrations.toggle');
        Route::resource('integrations', IntegrationController::class);

        // Channel Credentials
        Route::prefix('channel-credentials')->name('channel-credentials.')->group(function () {
            Route::get('/', [ChannelCredentialsController::class, 'index'])->name('index');
            Route::get('{channel}', [ChannelCredentialsController::class, 'show'])->name('show');
            Route::get('{channel}/check', [ChannelCredentialsController::class, 'checkConfiguration'])->name('check');
        });

        // Channel Management
        Route::prefix('channels')->name('channels.')->group(function () {

            Route::get('dashboard', [ApiUsageDashboardController::class, 'index'])->name('dashboard');

            Route::get('add', [ApiUsageDashboardController::class, 'add'])->name('add');
            // Web Channels
            Route::prefix('webs')->name('webs.')->group(function () {
                Route::get('/', [WebController::class, 'index'])->name('index');
                Route::get('create', [WebController::class, 'create'])->name('create');
                Route::post('/', [WebController::class, 'store'])->name('store');
                Route::get('{webWidget}/show', [WebController::class, 'show'])->name('show');
                Route::get('{webWidget}/edit', [WebController::class, 'edit'])->name('edit');
                Route::put('{webWidget}', [WebController::class, 'update'])->name('update');
                Route::delete('{webWidget}', [WebController::class, 'destroy'])->name('destroy');
            });

            // Facebook
            Route::prefix('facebook')->name('facebook-pages.')->group(function () {
                Route::get('/', [FacebookController::class, 'index'])->name('index');
                Route::get('create', [FacebookController::class, 'create'])->name('create');
                Route::post('/', [FacebookController::class, 'store'])->name('store');
                Route::get('{facebookPage}/show', [FacebookController::class, 'show'])->name('show');
                Route::get('{facebookPage}/edit', [FacebookController::class, 'edit'])->name('edit');
                Route::put('{facebookPage}', [FacebookController::class, 'update'])->name('update');
                Route::delete('{facebookPage}', [FacebookController::class, 'destroy'])->name('destroy');
                Route::get('callback', [FacebookController::class, 'callback'])->name('callback');
                Route::get('select', [FacebookController::class, 'select'])->name('select');
                Route::post('{facebookPage}/reauthorize', [FacebookController::class, 'reauthorize'])->name('reauthorize');
            });

            // Instagram
            Route::prefix('instagrams')->name('instagrams.')->group(function () {
                Route::get('/', [InstagramController::class, 'index'])->name('index');
                Route::get('create', [InstagramController::class, 'create'])->name('create');
                Route::post('/', [InstagramController::class, 'store'])->name('store');
                Route::get('{instagram}/show', [InstagramController::class, 'show'])->name('show');
                Route::get('{instagram}/edit', [InstagramController::class, 'edit'])->name('edit');
                Route::put('{instagram}', [InstagramController::class, 'update'])->name('update');
                Route::delete('{instagram}', [InstagramController::class, 'destroy'])->name('destroy');
                Route::get('callback', [InstagramController::class, 'callback'])->name('callback');
                Route::get('select', [InstagramController::class, 'select'])->name('select');
                Route::post('{instagram}/reauthorize', [InstagramController::class, 'reauthorize'])->name('reauthorize');
                Route::post('{instagram}/refresh-token', [InstagramController::class, 'refreshToken'])->name('refresh-token');
            });

            // WhatsApp — sync and connection-status are sensitive operations
            Route::prefix('whatsapps')->name('whatsapps.')->group(function () {
                Route::get('/', [WhatsappController::class, 'index'])->name('index');
                Route::get('create', [WhatsappController::class, 'create'])->name('create');
                Route::post('/', [WhatsappController::class, 'store'])->name('store');
                Route::get('{whatsapp}/show', [WhatsappController::class, 'show'])->name('show');
                Route::get('{whatsapp}/edit', [WhatsappController::class, 'edit'])->name('edit');
                Route::put('{whatsapp}', [WhatsappController::class, 'update'])->name('update');
                Route::delete('{whatsapp}', [WhatsappController::class, 'destroy'])->name('destroy');
                Route::post('{whatsapp}/sync-templates', [WhatsappController::class, 'syncTemplates'])
                    ->middleware('throttle:chat.sensitive')
                    ->name('sync-templates');
                Route::post('{whatsapp}/verify-connection', [WhatsappController::class, 'verifyConnection'])
                    ->middleware('throttle:chat.sensitive')
                    ->name('verify-connection');
                Route::post('{whatsapp}/connection-status', [WhatsappController::class, 'connectionStatus'])->name('connection-status');
                Route::get('{whatsapp}/qr-code', [WhatsappController::class, 'qrCode'])->name('qr-code');

                // WhatsApp Webhook Logs
                Route::prefix('{whatsapp}/logs')->name('logs.')->group(function () {
                    Route::get('/', [WhatsappWebhookLogController::class, 'index'])->name('index');
                    Route::get('list', [WhatsappWebhookLogController::class, 'list'])->name('list');
                    Route::get('{log}', [WhatsappWebhookLogController::class, 'show'])->name('show');
                    Route::delete('{log}', [WhatsappWebhookLogController::class, 'destroy'])->name('destroy');
                    Route::post('clear', [WhatsappWebhookLogController::class, 'clear'])->name('clear');
                });
            });

            // SMS
            Route::prefix('sms')->name('sms.')->group(function () {
                Route::get('/', [SmsController::class, 'index'])->name('index');
                Route::get('create', [SmsController::class, 'create'])->name('create');
                Route::post('/', [SmsController::class, 'store'])->name('store');
                Route::get('{sms}/show', [SmsController::class, 'show'])->name('show');
                Route::get('{sms}/edit', [SmsController::class, 'edit'])->name('edit');
                Route::put('{sms}', [SmsController::class, 'update'])->name('update');
                Route::delete('{sms}', [SmsController::class, 'destroy'])->name('destroy');
                Route::post('test-connection', [SmsController::class, 'testConnection'])
                    ->middleware('throttle:chat.sensitive')
                    ->name('test-connection');
            });

            // API channels — HMAC regeneration is sensitive
            Route::prefix('api')->name('api.')->group(function () {
                Route::get('/', [ApiController::class, 'index'])->name('index');
                Route::get('create', [ApiController::class, 'create'])->name('create');
                Route::post('/', [ApiController::class, 'store'])->name('store');
                Route::get('{api}/show', [ApiController::class, 'show'])->name('show');
                Route::get('{api}/edit', [ApiController::class, 'edit'])->name('edit');
                Route::put('{api}', [ApiController::class, 'update'])->name('update');
                Route::delete('{api}', [ApiController::class, 'destroy'])->name('destroy');
                Route::post('test-connection', [ApiController::class, 'testConnection'])->name('test-connection');
                Route::post('regenerate-hmac', [ApiController::class, 'regenerateHmacToken'])
                    ->middleware('throttle:chat.sensitive')
                    ->name('regenerate-hmac');
            });

            // Email Channels — SMTP testing is a sensitive external operation
            Route::prefix('emails')->name('emails.')->group(function () {
                Route::get('/', [EmailController::class, 'index'])->name('index');
                Route::get('create', [EmailController::class, 'create'])->name('create');
                Route::post('/', [EmailController::class, 'store'])->name('store');
                Route::get('{email}/show', [EmailController::class, 'show'])->name('show');
                Route::get('{email}/edit', [EmailController::class, 'edit'])->name('edit');
                Route::put('{email}', [EmailController::class, 'update'])->name('update');
                Route::delete('{email}', [EmailController::class, 'destroy'])->name('destroy');
                Route::post('{email}/test-smtp', [EmailController::class, 'testSmtp'])
                    ->middleware('throttle:chat.sensitive')
                    ->name('test-smtp');
            });

        });

        // Saved Views
        Route::prefix('saved-views')->name('saved-views.')->group(function () {
            Route::get('/', [SavedViewController::class, 'index'])->name('index');
            Route::post('/', [SavedViewController::class, 'store'])->name('store');
            Route::get('{savedView}', [SavedViewController::class, 'show'])->name('show');
        });

        // SLA Policies
        Route::resource('sla-policies', SlaPolicyController::class);

        // Reports — export is an import-class operation (can be expensive)
        Route::prefix('reports')->name('reports.')->group(function () {

            Route::get('sla', [SlaReportController::class, 'index'])->name('sla.index');
            Route::get('csat', [CsatReportController::class, 'index'])->name('csat.index');
            Route::get('contacts', [CustomerReportController::class, 'index'])->name('contacts.index');
            Route::post('contacts/export', [CustomerReportController::class, 'export'])
                ->middleware('throttle:chat.imports')
                ->name('contacts.export');

            Route::prefix('agent-performance')->name('agent-performance.')->group(function () {
                Route::get('/', [AgentPerformanceReportController::class, 'index'])->name('index');
                Route::get('overview', [AgentPerformanceReportController::class, 'overview'])->name('overview');
                Route::get('my-performance', [AgentPerformanceReportController::class, 'myPerformance'])->name('my-performance');
                Route::get('agents/{user}/metrics', [AgentPerformanceReportController::class, 'agentMetrics'])->name('agents.metrics');
                Route::get('leaderboard', [AgentPerformanceReportController::class, 'leaderboard'])->name('leaderboard');
            });

            Route::prefix('analytics')->name('analytics.')->group(function () {
                Route::get('/', [AnalyticsDashboardController::class, 'index'])->name('index');
            });

            Route::prefix('team-workload')->name('team-workload.')->group(function () {
                Route::get('/', [TeamWorkloadBalancingController::class, 'index'])->name('index');
                Route::get('{team}/agents', [TeamWorkloadBalancingController::class, 'agents'])->name('agents');
            });
        });

        // Audit Logs
        Route::get('audits', [AuditLogController::class, 'index'])->name('audits.index');

    });

// ====================================================================
// CONVERSATION ROUTES - /conversations/* (Conversations & Customers)
// Access controlled via Spatie permissions in controllers
// ====================================================================
Route::prefix('chat')
    ->name('chat.')
    ->middleware(['web', 'auth'])
    ->group(function () {

        Route::prefix('helpcenter')->group(function () {
            // Main Index
            Route::get('/', [HelpcenterController::class, 'index'])->name('manager.chat.helpcenter.index');

            // Categories
            Route::get('/categories', [HelpcenterController::class, 'index'])->name('manager.chat.helpcenter.categories');
            Route::get('/categories/create', [HelpcenterController::class, 'create'])->name('manager.chat.helpcenter.categories.create');
            Route::post('/categories/store', [HelpcenterController::class, 'store'])->name('manager.chat.helpcenter.categories.store');
            Route::get('/categories/{id}', [HelpcenterController::class, 'showCategory'])->name('manager.chat.helpcenter.categories.show');
            Route::get('/categories/edit/{id}', [HelpcenterController::class, 'edit'])->name('manager.chat.helpcenter.categories.edit');
            Route::post('/categories/update', [HelpcenterController::class, 'update'])->name('manager.chat.helpcenter.categories.update');
            Route::delete('/categories/{id}', [HelpcenterController::class, 'destroy'])->name('manager.chat.helpcenter.categories.destroy');

            // Sections
            Route::get('/sections/create', [HelpcenterController::class, 'createSection'])->name('manager.chat.helpcenter.sections.create');
            Route::post('/sections/store', [HelpcenterController::class, 'storeSection'])->name('manager.chat.helpcenter.sections.store');
            Route::get('/sections/{id}', [HelpcenterController::class, 'showSection'])->name('manager.chat.helpcenter.sections.show');
            Route::get('/sections/{id}/edit', [HelpcenterController::class, 'editSection'])->name('manager.chat.helpcenter.sections.edit');
            Route::post('/sections/update', [HelpcenterController::class, 'updateSection'])->name('manager.chat.helpcenter.sections.update');
            Route::delete('/sections/{id}', [HelpcenterController::class, 'destroySection'])->name('manager.chat.helpcenter.sections.destroy');
            Route::get('/sections/{id}/articles/create', [HelpcenterController::class, 'createArticleInSection'])->name('manager.chat.helpcenter.sections.articles.create');

            // Articles
            Route::get('/articles', [HelpcenterController::class, 'articlesIndex'])->name('manager.chat.helpcenter.articles');
            Route::get('/articles/create', [HelpcenterController::class, 'createArticle'])->name('manager.chat.helpcenter.articles.create');
            Route::post('/articles/store', [HelpcenterController::class, 'storeArticle'])->name('manager.chat.helpcenter.articles.store');
            Route::get('/articles/edit/{id}', [HelpcenterController::class, 'editArticle'])->name('manager.chat.helpcenter.articles.edit');
            Route::post('/articles/update', [HelpcenterController::class, 'updateArticle'])->name('manager.chat.helpcenter.articles.update');
            Route::delete('/articles/{id}', [HelpcenterController::class, 'destroyArticle'])->name('manager.chat.helpcenter.articles.destroy');
        });

        // Search page & API endpoints
        Route::prefix('search')->name('search.')->group(function () {
            Route::get('/', [SearchController::class, 'index'])->name('index');
            Route::get('/contacts', [SearchController::class, 'contacts'])->name('contacts');
            Route::get('/conversations', [SearchController::class, 'conversations'])->name('conversations');
            Route::get('/messages', [SearchController::class, 'messages'])->name('messages');
        });

        Route::prefix('conversations')->name('conversations.')->group(function () {
            // Dashboard style filters
            Route::get('/', [ConversationController::class, 'index'])->name('index');
            Route::get('/mine', [ConversationController::class, 'mine'])->name('mine');
            Route::get('/unassigned', [ConversationController::class, 'unassigned'])->name('unassigned');
            Route::get('/mentions', [ConversationController::class, 'mentions'])->name('mentions');
            Route::get('/unattended', [ConversationController::class, 'unattended'])->name('unattended');

            // Search endpoints (before wildcard routes)
            Route::get('/search', [ConversationController::class, 'search'])->name('search');
            Route::get('/search-contacts', [ConversationController::class, 'searchContacts'])->name('searchContacts');

            // Cursor pagination load-more endpoint
            Route::get('/load-more', [ConversationController::class, 'loadMore'])->name('loadMore');

            // Filtros por recurso
            Route::get('/inbox/{inbox}', [ConversationController::class, 'byInbox'])->name('byInbox');
            Route::get('/team/{team}', [ConversationController::class, 'byTeam'])->name('byTeam');
            Route::get('/label/{label}', [ConversationController::class, 'byLabel'])->name('byLabel');

            // Export (before wildcard {conversation} to avoid route conflicts)
            Route::get('/export-excel', [ConversationExportController::class, 'exportToExcel'])->name('exportExcel');

            // Crear nueva conversacion
            Route::post('/store', [ConversationController::class, 'store'])->name('store');

            // Get variable data for template rendering
            Route::get('/{conversation}/variables', [ConversationController::class, 'getVariableData'])->name('variables');

            // Search message templates for autocomplete
            Route::get('/{conversation}/message-templates/search', [MessageTemplateController::class, 'search'])->name('templates.search');

            // Wildcard conversation routes (MUST be after literal routes)
            Route::get('/{conversation}', [ConversationController::class, 'show'])->name('show');
            Route::put('/{conversation}', [ConversationController::class, 'update'])->name('update');
            Route::delete('/{conversation}', [ConversationController::class, 'destroy'])->name('destroy');

            // Exports
            Route::get('/{conversation}/export-pdf', [ConversationExportController::class, 'exportToPdf'])->name('exportPdf');
            Route::get('/{conversation}/print', [ConversationExportController::class, 'printView'])->name('print');
            Route::post('/{conversation}/email-transcript', [ConversationExportController::class, 'emailTranscript'])->name('emailTranscript');

            // Status management
            Route::patch('/{conversation}/status', [ConversationStatusController::class, 'updateStatus'])->name('updateStatus');
            Route::post('/{conversation}/close', [ConversationStatusController::class, 'close'])->name('close');
            Route::post('/{conversation}/reopen', [ConversationStatusController::class, 'reopen'])->name('reopen');
            Route::post('/{conversation}/snooze', [ConversationStatusController::class, 'snooze'])->name('snooze');
            Route::post('/{conversation}/unsnooze', [ConversationStatusController::class, 'unsnooze'])->name('unsnooze');

            // Assignment management
            Route::patch('/{conversation}/assign', [ConversationAssignmentController::class, 'assign'])->name('assign');
            Route::patch('/{conversation}/team', [ConversationAssignmentController::class, 'updateTeam'])->name('updateTeam');
            Route::patch('/{conversation}/priority', [ConversationAssignmentController::class, 'updatePriority'])->name('updatePriority');

            // Label management
            Route::post('/{conversation}/labels', [ConversationLabelController::class, 'addLabels'])->name('addLabels');
            Route::patch('/{conversation}/labels', [ConversationLabelController::class, 'updateLabels'])->name('updateLabels');
            Route::delete('/{conversation}/labels', [ConversationLabelController::class, 'removeLabel'])->name('removeLabel');

            // Macro execution on conversation
            Route::post('/{conversation}/macros/{macro}/execute', [ConversationMacroController::class, 'execute'])->name('macros.execute');

            // Messages within a conversation
            Route::post('/{conversation}/messages', [MessageController::class, 'store'])->name('messages.store');

            // Typing indicator
            Route::post('/{conversation}/typing', [TypingIndicatorController::class, '__invoke'])->name('typing');

            // Conversation Routing
            Route::post('/{conversation}/auto-assign', [ConversationRoutingController::class, 'autoAssign'])->name('auto-assign');
            Route::get('/{conversation}/suggest-agent', [ConversationRoutingController::class, 'suggest'])->name('suggest-agent');
        });

        // Customers
        Route::prefix('customers')->name('customers.')->group(function () {
            Route::get('/', [CustomerController::class, 'index'])->name('index');
            Route::get('create', [CustomerController::class, 'create'])->name('create');
            Route::post('/', [CustomerController::class, 'store'])->name('store');

            // Customer Segments (before wildcard {customer} to avoid route conflicts)
            Route::prefix('segments')->name('segments.')->group(function () {
                Route::get('/', [CustomerSegmentController::class, 'index'])->name('index');
                Route::get('create', [CustomerSegmentController::class, 'create'])->name('create');
                Route::post('/', [CustomerSegmentController::class, 'store'])->name('store');
                Route::get('{segment}', [CustomerSegmentController::class, 'show'])->name('show');
                Route::get('{segment}/edit', [CustomerSegmentController::class, 'edit'])->name('edit');
                Route::put('{segment}', [CustomerSegmentController::class, 'update'])->name('update');
                Route::delete('{segment}', [CustomerSegmentController::class, 'destroy'])->name('destroy');
                Route::post('{segment}/refresh', [CustomerSegmentController::class, 'refresh'])->name('refresh');
                Route::post('{segment}/add-contacts', [CustomerSegmentController::class, 'addContacts'])->name('add-contacts');
                Route::post('{segment}/remove-contacts', [CustomerSegmentController::class, 'removeContacts'])->name('remove-contacts');
                Route::post('/preview', [CustomerSegmentController::class, 'preview'])->name('preview');
            });

            // Customer Merge (before wildcard {customer})
            Route::prefix('merge')->name('merge.')->group(function () {
                Route::get('/', [CustomerMergeController::class, 'index'])->name('index');
                Route::post('find-duplicates', [CustomerMergeController::class, 'findDuplicates'])->name('find-duplicates');
                Route::post('preview', [CustomerMergeController::class, 'previewMerge'])->name('preview');
                Route::post('execute', [CustomerMergeController::class, 'executeMerge'])->name('execute');
            });

            // Customer Import (before wildcard {customer})
            Route::get('import', [CustomerController::class, 'importForm'])->name('import');
            Route::post('import', [CustomerController::class, 'import'])->name('import.process');
            Route::get('export', [CustomerController::class, 'export'])->name('export');

            Route::prefix('import')->name('import.')->group(function () {
                Route::get('advanced', [CustomerImportController::class, 'showAdvanced'])->name('advanced');
                Route::post('parse', [CustomerImportController::class, 'parseFile'])->name('parse');
                Route::post('preview', [CustomerImportController::class, 'preview'])->name('preview');
                Route::post('execute', [CustomerImportController::class, 'execute'])->name('execute');
                Route::get('template', [CustomerImportController::class, 'downloadTemplate'])->name('template');
                Route::post('advanced/process', [CustomerImportController::class, 'execute'])->name('advanced.process');
            });

            // Wildcard customer routes (MUST be after literal prefix groups)
            Route::get('{customer}', [CustomerController::class, 'show'])->name('show');
            Route::get('{customer}/edit', [CustomerController::class, 'edit'])->name('edit');
            Route::put('{customer}', [CustomerController::class, 'update'])->name('update');
            Route::delete('{customer}', [CustomerController::class, 'destroy'])->name('destroy');
            Route::post('{customer}/restore', [CustomerController::class, 'restore'])->name('restore');
            Route::post('{customer}/ban', [CustomerController::class, 'ban'])->name('ban');
            Route::post('{customer}/unban', [CustomerController::class, 'unban'])->name('unban');

            // Customer Labels
            Route::post('{customer}/labels', [CustomerController::class, 'addLabel'])->name('labels.add');
            Route::delete('{customer}/labels/{label}', [CustomerController::class, 'removeLabel'])->name('labels.remove');

            // Customer Notes
            Route::post('{customer}/notes', [CustomerController::class, 'storeNote'])->name('notes.store');
            Route::delete('{customer}/notes/{note}', [CustomerController::class, 'destroyNote'])->name('notes.destroy');

            // Customer Custom Attributes
            Route::post('{customer}/custom-attributes', [CustomerController::class, 'updateCustomAttributes'])->name('custom-attributes.update');
        });

    });

// ====================================================================
// PUBLIC ROUTES - Widget and Public APIs (no authentication)
// ====================================================================

// Widget Demo Page
Route::get('/pages/widget/{websiteToken?}', [DemoController::class, 'widget'])->name('pages.widget');

// Widget SDK Routes
Route::prefix('widget')->name('widget.')->group(function () {
    Route::get('/script/{websiteToken}', [WidgetScriptController::class, 'script'])->name('script');
    Route::get('/config/{websiteToken}', [WidgetScriptController::class, 'config'])->name('config');
});

// Widget API Routes
Route::prefix('api/widget/{websiteToken}')
    ->middleware('throttle:60,1')
    ->name('api.widget.')
    ->group(function () {
        Route::post('/init', [WidgetApiController::class, 'initConversation'])->name('init');
        Route::post('/send', [WidgetApiController::class, 'sendMessage'])
            ->middleware('widget.message.ratelimit')
            ->name('send');
        Route::get('/messages/{conversationId}', [WidgetApiController::class, 'getMessages'])->name('messages');
        Route::get('/availability', [WidgetApiController::class, 'checkAvailability'])->name('availability');
    });

// LiveChat Widget - Public routes
Route::prefix('lc')->name('lc.')->group(function () {
    Route::get('/widget', [WidgetController::class, 'index'])->name('widget');
    Route::get('/launcher-pages', [WidgetController::class, 'launcherDemo'])->name('launcher-pages');
    Route::get('/api/settings', [WidgetController::class, 'settings'])->name('widget.settings');
    Route::get('/api/helpcenter', [HelpcenterController::class, 'apiWidget'])->name('widget.helpcenter');
    Route::get('/api/helpcenter/articles/{id}', [HelpcenterController::class, 'apiArticle'])->name('widget.helpcenter.article');

    // Widget Conversations API
    Route::middleware('throttle:60,1')->group(function () {
        Route::post('/api/conversation', [WidgetConversationController::class, 'store'])->name('api.conversation.store');
        Route::get('/api/conversation/{id}', [WidgetConversationController::class, 'show'])->name('api.conversation.show');
        Route::post('/api/conversation/{id}/messages', [WidgetConversationController::class, 'sendMessage'])
            ->middleware('widget.message.ratelimit')
            ->name('api.conversation.messages.send');
        Route::get('/api/conversation/{id}/messages', [WidgetConversationController::class, 'getMessages'])->name('api.conversation.messages.index');
        Route::post('/api/conversation/{id}/close', [WidgetConversationController::class, 'close'])->name('api.conversation.close');
    });

    // Catch-all for React Router
    Route::get('/widget/{any?}', [WidgetController::class, 'index'])
        ->where('any', '.*')
        ->name('widget.catchall');
});

// Alternative launcher pages route
Route::get('/livechat/launcher-pages', [WidgetController::class, 'launcherDemo'])->name('livechat.launcher-pages');
