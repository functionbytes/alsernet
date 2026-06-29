<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskEmailLog\Http\Controllers\EmailLogController;
use Modules\HelpdeskEmailLog\Http\Controllers\Settings\EmailLogSettingsController;

// The `web` middleware group is applied by the service provider.
Route::middleware('auth')
    ->prefix('panel/helpdeskemaillog')
    ->name('helpdeskemaillog.')
    ->group(function () {
        Route::get('/', [EmailLogController::class, 'index'])->name('index');

        Route::middleware('throttle:6,1')
            ->get('/export', [EmailLogController::class, 'export'])
            ->name('export');

        Route::get('/{emailLog}', [EmailLogController::class, 'show'])->name('show')->whereUuid('emailLog');

        Route::middleware('throttle:12,1')
            ->post('/{emailLog}/resend', [EmailLogController::class, 'resend'])
            ->name('resend')
            ->whereUuid('emailLog');

        Route::delete('/bulk', [EmailLogController::class, 'bulkDestroy'])->name('bulk-destroy');
        Route::delete('/{emailLog}', [EmailLogController::class, 'destroy'])->name('destroy')->whereUuid('emailLog');
    });

Route::middleware('auth')
    ->prefix('panel/settings/helpdeskemaillog')
    ->name('settings.helpdeskemaillog.')
    ->group(function () {
        Route::get('/', [EmailLogSettingsController::class, 'index'])->name('index');
        Route::patch('/', [EmailLogSettingsController::class, 'update'])->name('update');
    });
