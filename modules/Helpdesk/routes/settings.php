<?php

use Illuminate\Support\Facades\Route;
use Modules\Helpdesk\Http\Controllers\Managers\Settings\AgentSettingsController;
use Modules\Helpdesk\Http\Controllers\Managers\Settings\AttributesController;
use Modules\Helpdesk\Http\Controllers\Managers\Settings\AuditController;
use Modules\Helpdesk\Http\Controllers\Managers\Settings\AutomationRulesController;
use Modules\Helpdesk\Http\Controllers\Managers\Settings\BannersController;
use Modules\Helpdesk\Http\Controllers\Managers\Settings\BusinessHoursController;
use Modules\Helpdesk\Http\Controllers\Managers\Settings\CannedRepliesController;
use Modules\Helpdesk\Http\Controllers\Managers\Settings\EmailSettingsController;
use Modules\Helpdesk\Http\Controllers\Managers\Settings\InboxesController;
use Modules\Helpdesk\Http\Controllers\Managers\Settings\MacrosController;
use Modules\Helpdesk\Http\Controllers\Managers\Settings\NotificationSettingsController;
use Modules\Helpdesk\Http\Controllers\Managers\Settings\PreChatFormsController;
use Modules\Helpdesk\Http\Controllers\Managers\Settings\RoutingRulesController;
use Modules\Helpdesk\Http\Controllers\Managers\Settings\SettingsController;
use Modules\Helpdesk\Http\Controllers\Managers\Settings\SlaPoliciesController;
use Modules\Helpdesk\Http\Controllers\Managers\Settings\StatusesController;
use Modules\Helpdesk\Http\Controllers\Managers\Settings\StatusPageController;
use Modules\Helpdesk\Http\Controllers\Managers\Settings\SurveysController;
use Modules\Helpdesk\Http\Controllers\Managers\Settings\TagsController;
use Modules\Helpdesk\Http\Controllers\Managers\Settings\TeamController;
use Modules\Helpdesk\Http\Controllers\Managers\Settings\ViewsController;
use Modules\Helpdesk\Http\Controllers\Managers\Settings\WebhooksController;
use Modules\Helpdesk\Http\Controllers\Managers\SocialIntegrationsController;

// Notification Settings
Route::get('notifications', [NotificationSettingsController::class, 'index'])->name('notifications');
Route::put('notifications', [NotificationSettingsController::class, 'update'])->name('notifications.update');

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

// Canned Replies
Route::prefix('canned-replies')->name('canned-replies.')->group(function () {
    Route::get('/', [CannedRepliesController::class, 'index'])->name('index');
    Route::get('create', [CannedRepliesController::class, 'create'])->name('create');
    Route::post('/', [CannedRepliesController::class, 'store'])->name('store');
    Route::get('{canned_reply}/edit', [CannedRepliesController::class, 'edit'])->name('edit');
    Route::put('{canned_reply}', [CannedRepliesController::class, 'update'])->name('update');
    Route::delete('{canned_reply}', [CannedRepliesController::class, 'destroy'])->name('destroy');
});

// Automation Rules
Route::prefix('automation-rules')->name('automation-rules.')->group(function () {
    Route::get('/', [AutomationRulesController::class, 'index'])->name('index');
    Route::get('create', [AutomationRulesController::class, 'create'])->name('create');
    Route::post('/', [AutomationRulesController::class, 'store'])->name('store');
    Route::get('{automationRule}/edit', [AutomationRulesController::class, 'edit'])->name('edit');
    Route::put('{automationRule}', [AutomationRulesController::class, 'update'])->name('update');
    Route::delete('{automationRule}', [AutomationRulesController::class, 'destroy'])->name('destroy');
    Route::post('{automationRule}/toggle', [AutomationRulesController::class, 'toggle'])->name('toggle');
});

// Outbound Webhooks
Route::prefix('webhooks')->name('webhooks.')->group(function () {
    Route::get('/', [WebhooksController::class, 'index'])->name('index');
    Route::get('create', [WebhooksController::class, 'create'])->name('create');
    Route::post('/', [WebhooksController::class, 'store'])->name('store');
    Route::get('{webhook}/edit', [WebhooksController::class, 'edit'])->name('edit');
    Route::put('{webhook}', [WebhooksController::class, 'update'])->name('update');
    Route::delete('{webhook}', [WebhooksController::class, 'destroy'])->name('destroy');
});

// Schedule routes moved to modules/HelpdeskAgents/routes/settings.php

// Tickets general settings (custom ticket ID, char limit, etc.)
Route::get('tickets', [SettingsController::class, 'ticketsIndex'])->name('tickets');
Route::put('tickets', [SettingsController::class, 'ticketsUpdate'])->name('tickets.update');

// Team
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

// Attributes
Route::prefix('attributes')->name('attributes.')->group(function () {
    Route::get('/', [AttributesController::class, 'index'])->name('index');
    Route::get('create', [AttributesController::class, 'create'])->name('create');
    Route::post('/', [AttributesController::class, 'store'])->name('store');
    Route::get('{id}/edit', [AttributesController::class, 'edit'])->name('edit');
    Route::put('{id}', [AttributesController::class, 'update'])->name('update');
    Route::delete('{id}', [AttributesController::class, 'destroy'])->name('destroy');
    Route::patch('{id}/toggle', [AttributesController::class, 'toggleActive'])->name('toggle');
});

// Tags
Route::prefix('tags')->name('tags.')->group(function () {
    Route::get('/', [TagsController::class, 'index'])->name('index');
    Route::get('create', [TagsController::class, 'create'])->name('create');
    Route::post('/', [TagsController::class, 'store'])->name('store');
    Route::get('{tag}/edit', [TagsController::class, 'edit'])->name('edit');
    Route::put('{tag}', [TagsController::class, 'update'])->name('update');
    Route::delete('{tag}', [TagsController::class, 'destroy'])->name('destroy');
});

// Conversation Statuses
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

// Inboxes (multi-canal)
Route::prefix('inboxes')->name('inboxes.')->group(function () {
    Route::get('/', [InboxesController::class, 'index'])->name('index');
    Route::get('create', [InboxesController::class, 'create'])->name('create');
    Route::post('/', [InboxesController::class, 'store'])->name('store');
    Route::get('{inbox}/edit', [InboxesController::class, 'edit'])->name('edit');
    Route::put('{inbox}', [InboxesController::class, 'update'])->name('update');
    Route::delete('{inbox}', [InboxesController::class, 'destroy'])->name('destroy');
    Route::patch('{inbox}/toggle', [InboxesController::class, 'toggle'])->name('toggle');
});

// Conversation Views
Route::prefix('views')->name('views.')->group(function () {
    Route::get('/', [ViewsController::class, 'index'])->name('index');
    Route::get('create', [ViewsController::class, 'create'])->name('create');
    Route::post('/', [ViewsController::class, 'store'])->name('store');
    Route::get('{view}/edit', [ViewsController::class, 'edit'])->name('edit');
    Route::put('{view}', [ViewsController::class, 'update'])->name('update');
    Route::delete('{view}', [ViewsController::class, 'destroy'])->name('destroy');
    Route::post('reorder', [ViewsController::class, 'reorder'])->name('reorder');
});

// Business Hours
Route::get('business-hours', [BusinessHoursController::class, 'index'])->name('business-hours');
Route::put('business-hours', [BusinessHoursController::class, 'update'])->name('business-hours.update');
Route::post('business-hours/reset', [BusinessHoursController::class, 'reset'])->name('business-hours.reset');

// SLA Policies
Route::prefix('sla-policies')->name('sla-policies.')->group(function () {
    Route::get('/', [SlaPoliciesController::class, 'index'])->name('index');
    Route::get('create', [SlaPoliciesController::class, 'create'])->name('create');
    Route::post('/', [SlaPoliciesController::class, 'store'])->name('store');
    Route::get('{slaPolicy}/edit', [SlaPoliciesController::class, 'edit'])->name('edit');
    Route::put('{slaPolicy}', [SlaPoliciesController::class, 'update'])->name('update');
    Route::delete('{slaPolicy}', [SlaPoliciesController::class, 'destroy'])->name('destroy');
    Route::post('{slaPolicy}/toggle', [SlaPoliciesController::class, 'toggle'])->name('toggle');
});

// Macros
Route::prefix('macros')->name('macros.')->group(function () {
    Route::get('/', [MacrosController::class, 'index'])->name('index');
    Route::get('create', [MacrosController::class, 'create'])->name('create');
    Route::post('/', [MacrosController::class, 'store'])->name('store');
    Route::get('{macro}/edit', [MacrosController::class, 'edit'])->name('edit');
    Route::put('{macro}', [MacrosController::class, 'update'])->name('update');
    Route::delete('{macro}', [MacrosController::class, 'destroy'])->name('destroy');
});

// Email settings
Route::get('email', [EmailSettingsController::class, 'index'])->name('email');
Route::put('email', [EmailSettingsController::class, 'update'])->name('email.update');

// Audit log
Route::get('audits', [AuditController::class, 'index'])->name('audits.index');

// Pre-chat forms
Route::prefix('pre-chat-forms')->name('pre-chat-forms.')->group(function () {
    Route::get('/', [PreChatFormsController::class, 'index'])->name('index');
    Route::get('create', [PreChatFormsController::class, 'create'])->name('create');
    Route::post('/', [PreChatFormsController::class, 'store'])->name('store');
    Route::get('{preChatForm}/edit', [PreChatFormsController::class, 'edit'])->name('edit');
    Route::put('{preChatForm}', [PreChatFormsController::class, 'update'])->name('update');
    Route::delete('{preChatForm}', [PreChatFormsController::class, 'destroy'])->name('destroy');
});

// Status page
Route::prefix('status')->name('status.')->group(function () {
    Route::get('components', [StatusPageController::class, 'componentsIndex'])->name('components');
    Route::get('components/create', [StatusPageController::class, 'componentCreate'])->name('components.create');
    Route::post('components', [StatusPageController::class, 'componentStore'])->name('components.store');
    Route::get('components/{statusComponent}/edit', [StatusPageController::class, 'componentEdit'])->name('components.edit');
    Route::put('components/{statusComponent}', [StatusPageController::class, 'componentUpdate'])->name('components.update');
    Route::delete('components/{statusComponent}', [StatusPageController::class, 'componentDestroy'])->name('components.destroy');

    Route::get('incidents', [StatusPageController::class, 'incidentsIndex'])->name('incidents');
    Route::get('incidents/create', [StatusPageController::class, 'incidentCreate'])->name('incidents.create');
    Route::post('incidents', [StatusPageController::class, 'incidentStore'])->name('incidents.store');
    Route::get('incidents/{statusIncident}/edit', [StatusPageController::class, 'incidentEdit'])->name('incidents.edit');
    Route::put('incidents/{statusIncident}', [StatusPageController::class, 'incidentUpdate'])->name('incidents.update');
    Route::delete('incidents/{statusIncident}', [StatusPageController::class, 'incidentDestroy'])->name('incidents.destroy');
});

// Banners
Route::prefix('banners')->name('banners.')->group(function () {
    Route::get('/', [BannersController::class, 'index'])->name('index');
    Route::get('create', [BannersController::class, 'create'])->name('create');
    Route::post('/', [BannersController::class, 'store'])->name('store');
    Route::get('{banner}/edit', [BannersController::class, 'edit'])->name('edit');
    Route::put('{banner}', [BannersController::class, 'update'])->name('update');
    Route::delete('{banner}', [BannersController::class, 'destroy'])->name('destroy');
});

// Surveys
Route::prefix('surveys')->name('surveys.')->group(function () {
    Route::get('/', [SurveysController::class, 'index'])->name('index');
    Route::get('create', [SurveysController::class, 'create'])->name('create');
    Route::post('/', [SurveysController::class, 'store'])->name('store');
    Route::get('{survey}/edit', [SurveysController::class, 'edit'])->name('edit');
    Route::put('{survey}', [SurveysController::class, 'update'])->name('update');
    Route::delete('{survey}', [SurveysController::class, 'destroy'])->name('destroy');
    Route::get('{survey}/responses', [SurveysController::class, 'responses'])->name('responses');
});

// Settings root index (overview de todas las settings)
Route::get('/', [SettingsController::class, 'livechatIndex'])->name('index');

// Routing rules (asignación automática por palabra clave)
Route::prefix('routing-rules')->name('routing-rules.')->group(function () {
    Route::get('/', [RoutingRulesController::class, 'index'])->name('index');
    Route::get('create', [RoutingRulesController::class, 'create'])->name('create');
    Route::post('/', [RoutingRulesController::class, 'store'])->name('store');
    Route::get('{rule}/edit', [RoutingRulesController::class, 'edit'])->name('edit');
    Route::put('{rule}', [RoutingRulesController::class, 'update'])->name('update');
    Route::delete('{rule}', [RoutingRulesController::class, 'destroy'])->name('destroy');
});

// Email accounts (IMAP/SMTP)
Route::prefix('email-accounts')->name('email-accounts.')->group(function () {
    Route::get('/', [EmailSettingsController::class, 'index'])->name('index');
    Route::get('create', [EmailSettingsController::class, 'create'])->name('create');
    Route::post('/', [EmailSettingsController::class, 'store'])->name('store');
    Route::get('{account}/edit', [EmailSettingsController::class, 'edit'])->name('edit');
    Route::put('{account}', [EmailSettingsController::class, 'update'])->name('update');
    Route::delete('{account}', [EmailSettingsController::class, 'destroy'])->name('destroy');
    Route::post('{account}/test', [EmailSettingsController::class, 'test'])->name('test');
});

// Status page components + incidents (admin)
Route::prefix('status')->name('status.')->group(function () {
    Route::get('/', [StatusPageController::class, 'index'])->name('index');
    Route::get('components/create', [StatusPageController::class, 'createComponent'])->name('components.create');
    Route::post('components', [StatusPageController::class, 'storeComponent'])->name('components.store');
    Route::put('components/{component}', [StatusPageController::class, 'updateComponent'])->name('components.update');
    Route::delete('components/{component}', [StatusPageController::class, 'destroyComponent'])->name('components.destroy');
    Route::get('incidents/create', [StatusPageController::class, 'createIncident'])->name('incidents.create');
    Route::post('incidents', [StatusPageController::class, 'storeIncident'])->name('incidents.store');
    Route::put('incidents/{incident}', [StatusPageController::class, 'updateIncident'])->name('incidents.update');
    Route::delete('incidents/{incident}', [StatusPageController::class, 'destroyIncident'])->name('incidents.destroy');
});

// Agent settings
Route::prefix('agent-settings')->name('agent-settings.')->group(function () {
    Route::get('/', [AgentSettingsController::class, 'index'])->name('index');
    Route::get('{user}/edit', [AgentSettingsController::class, 'edit'])->name('edit');
    Route::put('{user}', [AgentSettingsController::class, 'update'])->name('update');
});

// Skills
Route::prefix('skills')->name('skills.')->group(function () {
    Route::get('/', [SkillsController::class, 'index'])->name('index');
    Route::get('create', [SkillsController::class, 'create'])->name('create');
    Route::post('/', [SkillsController::class, 'store'])->name('store');
    Route::get('{skill}/edit', [SkillsController::class, 'edit'])->name('edit');
    Route::put('{skill}', [SkillsController::class, 'update'])->name('update');
    Route::delete('{skill}', [SkillsController::class, 'destroy'])->name('destroy');
});

// Companies
Route::prefix('companies')->name('companies.')->group(function () {
    Route::get('/', [CompaniesController::class, 'index'])->name('index');
    Route::get('create', [CompaniesController::class, 'create'])->name('create');
    Route::post('/', [CompaniesController::class, 'store'])->name('store');
    Route::get('{company}/edit', [CompaniesController::class, 'edit'])->name('edit');
    Route::put('{company}', [CompaniesController::class, 'update'])->name('update');
    Route::delete('{company}', [CompaniesController::class, 'destroy'])->name('destroy');
});

// Audit log viewer (también accesible desde /panel/helpdesk/audit)
Route::redirect('audit', '/panel/settings/helpdesk/audits');
