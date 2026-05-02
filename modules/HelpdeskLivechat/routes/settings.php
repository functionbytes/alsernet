<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskLivechat\Http\Controllers\Settings\LivechatSettingsController;
use Modules\HelpdeskLivechat\Http\Controllers\Settings\PreChatFormsController;

/*
|--------------------------------------------------------------------------
| HelpdeskLivechat settings routes
|--------------------------------------------------------------------------
| Mounted with prefix `panel/settings/helpdesk` and name `settings.helpdesk-livechat.`.
| Engagement-related admin (triggers, personalizations, platforms,
| automation, goals, webhook-logs, audit-logs) lives in the Engagement module.
*/

Route::get('livechat', [LivechatSettingsController::class, 'index'])->name('index');
Route::put('livechat', [LivechatSettingsController::class, 'update'])->name('update');

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
