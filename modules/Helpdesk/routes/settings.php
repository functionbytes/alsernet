<?php

use Illuminate\Support\Facades\Route;
use Modules\Helpdesk\Http\Controllers\Managers\Settings\AttributesController;
use Modules\Helpdesk\Http\Controllers\Managers\Settings\SettingsController;
use Modules\Helpdesk\Http\Controllers\Managers\Settings\StatusesController;
use Modules\Helpdesk\Http\Controllers\Managers\Settings\TagsController;
use Modules\Helpdesk\Http\Controllers\Managers\Settings\TeamController;
use Modules\Helpdesk\Http\Controllers\Managers\Settings\ViewsController;
use Modules\Helpdesk\Http\Controllers\Managers\Settings\WebhooksController;
use Modules\Helpdesk\Http\Controllers\Managers\SocialIntegrationsController;

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
