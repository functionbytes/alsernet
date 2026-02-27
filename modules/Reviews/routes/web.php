<?php

use Illuminate\Support\Facades\Route;
use Modules\Reviews\Http\Controllers\ReviewController;
use Modules\Reviews\Http\Controllers\ReviewDashboardController;
use Modules\Reviews\Http\Controllers\ReviewReplyController;
use Modules\Reviews\Http\Controllers\ReviewTemplateController;
use Modules\Reviews\Http\Controllers\Settings\GoogleConnectionController;
use Modules\Reviews\Http\Controllers\Settings\GoogleLocationController;
use Modules\Reviews\Http\Controllers\Settings\ReviewSettingsController;

Route::middleware(['web', 'auth'])->group(function () {
    Route::prefix('settings/reviews')->name('settings.reviews.')->group(function () {
        // OAuth callback (must be before resource routes)
        Route::get('oauth/callback', [GoogleConnectionController::class, 'callback'])->name('oauth.callback');

        // Specific connection routes (must be before resource routes)
        Route::delete('connections/bulk-revoke', [GoogleConnectionController::class, 'bulkRevoke'])->name('connections.bulk-revoke');
        Route::delete('connections/{connection}/revoke', [GoogleConnectionController::class, 'destroy'])->name('connections.revoke');
        Route::post('connections/{connection}/reconnect', [GoogleConnectionController::class, 'reconnect'])->name('connections.reconnect');

        // Resource routes (must be after specific routes)
        Route::resource('connections', GoogleConnectionController::class);

        Route::resource('locations', GoogleLocationController::class)->only(['index', 'update']);
        Route::post('locations/bulk-sync', [GoogleLocationController::class, 'bulkSync'])->name('locations.bulk-sync');
        Route::post('locations/{location}/sync', [GoogleLocationController::class, 'sync'])->name('locations.sync');
        Route::post('locations/sync-all', [GoogleLocationController::class, 'syncAll'])->name('locations.sync-all');

        Route::get('config', [ReviewSettingsController::class, 'index'])->name('config.index');
        Route::match(['PUT', 'PATCH', 'POST'], 'config', [ReviewSettingsController::class, 'update'])->name('config.update');

        // Templates routes (in settings namespace)
        Route::get('templates', [ReviewTemplateController::class, 'index'])->name('templates.index');
        Route::post('templates', [ReviewTemplateController::class, 'store'])->name('templates.store');
        Route::get('templates/create', [ReviewTemplateController::class, 'create'])->name('templates.create');
        Route::delete('templates/bulk-delete', [ReviewTemplateController::class, 'bulkDelete'])->name('templates.bulk-delete');
        Route::get('templates/{template}', [ReviewTemplateController::class, 'show'])->name('templates.show');
        Route::get('templates/{template}/edit', [ReviewTemplateController::class, 'edit'])->name('templates.edit');
        Route::put('templates/{template}', [ReviewTemplateController::class, 'update'])->name('templates.update');
        Route::patch('templates/{template}/toggle-active', [ReviewTemplateController::class, 'toggleActive'])->name('templates.toggle-active');
        Route::delete('templates/{template}', [ReviewTemplateController::class, 'destroy'])->name('templates.destroy');
    });

    Route::prefix('reviews')->name('reviews.')->group(function () {
        Route::get('/', [ReviewDashboardController::class, 'index'])->name('dashboard');
        Route::get('dashboard/data', [ReviewDashboardController::class, 'data'])->name('dashboard.data');
        Route::get('list', [ReviewController::class, 'index'])->name('index');
        Route::get('data', [ReviewController::class, 'data'])->name('data');
        Route::get('export', [ReviewController::class, 'export'])->name('export');
        Route::get('export/download/{file}', [ReviewController::class, 'downloadExport'])->name('export.download');
        Route::post('bulk-moderate', [ReviewController::class, 'bulkModerate'])->name('bulk-moderate');

        Route::resource('replies', ReviewReplyController::class)->only(['store', 'update', 'destroy']);
        Route::post('replies/{reply}/publish', [ReviewReplyController::class, 'publish'])->name('replies.publish');

        Route::get('{review}', [ReviewController::class, 'show'])->name('show');
        Route::get('{review}/suggestions', [ReviewController::class, 'suggestions'])->name('suggestions');
        Route::patch('{review}/moderate', [ReviewController::class, 'moderate'])->name('moderate');
    });
});
