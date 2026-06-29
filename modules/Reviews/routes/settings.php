<?php

use Illuminate\Support\Facades\Route;
use Modules\Reviews\Http\Controllers\ReviewTemplateController;
use Modules\Reviews\Http\Controllers\Settings\AiSettingsController;
use Modules\Reviews\Http\Controllers\Settings\GoogleConnectionController;
use Modules\Reviews\Http\Controllers\Settings\GoogleLocationController;
use Modules\Reviews\Http\Controllers\Settings\NotificationPreferenceController;
use Modules\Reviews\Http\Controllers\Settings\ReviewImportController;
use Modules\Reviews\Http\Controllers\Settings\ReviewSettingsController;

Route::middleware(['web', 'auth'])->prefix('panel/settings/reviews')->name('settings.reviews.')->group(function () {
    // OAuth callback (must be before resource routes)
    Route::get('oauth/callback', [GoogleConnectionController::class, 'callback'])->name('oauth.callback');

    // Specific connection routes (must be before resource routes)
    Route::post('connections/bulk-action', [GoogleConnectionController::class, 'bulkAction'])->name('connections.bulk-action');
    Route::delete('connections/bulk-revoke', [GoogleConnectionController::class, 'bulkRevoke'])->name('connections.bulk-revoke');
    Route::delete('connections/{connection}/revoke', [GoogleConnectionController::class, 'destroy'])->name('connections.revoke');
    Route::post('connections/{connection}/reconnect', [GoogleConnectionController::class, 'reconnect'])->name('connections.reconnect');

    // Resource routes (must be after specific routes)
    Route::resource('connections', GoogleConnectionController::class);

    // Static location routes must come before resource (avoids {location} shadowing)
    Route::post('locations/bulk-action', [GoogleLocationController::class, 'bulkAction'])->name('locations.bulk-action');
    Route::post('locations/bulk-sync', [GoogleLocationController::class, 'bulkSync'])->name('locations.bulk-sync');
    Route::post('locations/sync-all', [GoogleLocationController::class, 'syncAll'])->name('locations.sync-all');
    Route::resource('locations', GoogleLocationController::class)->only(['index', 'create', 'store', 'update']);
    Route::post('locations/{location}/sync', [GoogleLocationController::class, 'sync'])->name('locations.sync');
    Route::get('locations/{location}/tags', [GoogleLocationController::class, 'tags'])->name('locations.tags.index');
    Route::post('locations/{location}/tags', [GoogleLocationController::class, 'storeTag'])->name('locations.tags.store');
    Route::delete('locations/{location}/tags/{slug}', [GoogleLocationController::class, 'destroyTag'])->name('locations.tags.destroy');

    Route::get('locations/{location}/import', [ReviewImportController::class, 'create'])->name('locations.import.create');
    Route::post('locations/{location}/import/csv', [ReviewImportController::class, 'storeCsv'])->name('locations.import.csv');
    Route::post('locations/{location}/import/manual', [ReviewImportController::class, 'storeManual'])->name('locations.import.manual');

    Route::get('config', [ReviewSettingsController::class, 'index'])->name('config.index');
    Route::match(['PUT', 'PATCH', 'POST'], 'config', [ReviewSettingsController::class, 'update'])->name('config.update');

    Route::get('widget', [ReviewSettingsController::class, 'widget'])->name('widget.index');

    Route::get('ai', [AiSettingsController::class, 'index'])->name('ai.index');
    Route::post('ai', [AiSettingsController::class, 'update'])->name('ai.update');
    Route::post('ai/test', [AiSettingsController::class, 'test'])->name('ai.test');

    Route::get('notifications', [NotificationPreferenceController::class, 'index'])->name('notifications.index');
    Route::post('notifications/update', [NotificationPreferenceController::class, 'update'])->name('notifications.update');
    Route::post('notifications/test/{type}', [NotificationPreferenceController::class, 'test'])->name('notifications.test');

    // Templates
    Route::get('templates', [ReviewTemplateController::class, 'index'])->name('templates.index');
    Route::post('templates', [ReviewTemplateController::class, 'store'])->name('templates.store');
    Route::get('templates/create', [ReviewTemplateController::class, 'create'])->name('templates.create');
    Route::post('templates/bulk-action', [ReviewTemplateController::class, 'bulkAction'])->name('templates.bulk-action');
    Route::delete('templates/bulk-delete', [ReviewTemplateController::class, 'bulkDelete'])->name('templates.bulk-delete');
    Route::get('templates/{template}', [ReviewTemplateController::class, 'show'])->name('templates.show');
    Route::get('templates/{template}/edit', [ReviewTemplateController::class, 'edit'])->name('templates.edit');
    Route::put('templates/{template}', [ReviewTemplateController::class, 'update'])->name('templates.update');
    Route::patch('templates/{template}/toggle-active', [ReviewTemplateController::class, 'toggleActive'])->name('templates.toggle-active');
    Route::delete('templates/{template}', [ReviewTemplateController::class, 'destroy'])->name('templates.destroy');
    Route::get('templates/{template}/versions', [ReviewTemplateController::class, 'versions'])->name('templates.versions');
    Route::post('templates/{template}/translations', [ReviewTemplateController::class, 'addTranslation'])->name('templates.translations.store');
    Route::delete('templates/{template}/translations/{language}', [ReviewTemplateController::class, 'removeTranslation'])->name('templates.translations.destroy');
});
