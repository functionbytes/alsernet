<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskLivechat\Http\Controllers\Settings\LivechatSettingsController;
use Modules\HelpdeskLivechat\Http\Controllers\Settings\PreChatFormsController;
use Modules\HelpdeskLivechat\Http\Controllers\Settings\WidgetTriggersController;

/*
|--------------------------------------------------------------------------
| HelpdeskLivechat settings routes
|--------------------------------------------------------------------------
| Mounted with prefix `panel/settings/helpdesk` and name `settings.helpdesk-livechat.`.
| La configuración del canal Web (livechat widget) se accede desde
| Settings > Helpdesk > Bandejas > canal Web.
*/

Route::get('livechat', [LivechatSettingsController::class, 'index'])->name('index');
Route::put('livechat', [LivechatSettingsController::class, 'update'])->name('update');

// AJAX list of Engagement PlatformIntegration records filtered by CMS platform.
// Returns [] if Engagement module is disabled.
Route::get('platform-integrations', [LivechatSettingsController::class, 'platformIntegrations'])
    ->name('platform-integrations');

Route::prefix('pre-chat-forms')
    ->name('pre-chat-forms.')
    ->group(function () {
        Route::get('/', [PreChatFormsController::class, 'index'])->name('index');
        Route::get('create', [PreChatFormsController::class, 'create'])->name('create');
        Route::post('/', [PreChatFormsController::class, 'store'])->name('store');
        Route::get('{preChatForm}/edit', [PreChatFormsController::class, 'edit'])->name('edit');
        Route::put('{preChatForm}', [PreChatFormsController::class, 'update'])->name('update');
        Route::delete('{preChatForm}', [PreChatFormsController::class, 'destroy'])->name('destroy');
    });

// Disparadores proactivos del chat web (live commerce).
Route::prefix('livechat/triggers')
    ->name('triggers.')
    ->group(function () {
        Route::get('/', [WidgetTriggersController::class, 'index'])->name('index');
        Route::get('create', [WidgetTriggersController::class, 'create'])->name('create');
        Route::post('/', [WidgetTriggersController::class, 'store'])->name('store');
        Route::get('{trigger}/edit', [WidgetTriggersController::class, 'edit'])->name('edit');
        Route::put('{trigger}', [WidgetTriggersController::class, 'update'])->name('update');
        Route::delete('{trigger}', [WidgetTriggersController::class, 'destroy'])->name('destroy');
    });
