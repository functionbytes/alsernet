<?php

use App\Http\Controllers\AlertThresholdController;
use App\Http\Controllers\ApiDocController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\GlobalSearchController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\Settings\SettingsPanelController;
use Illuminate\Support\Facades\Route;

// API documentation — publicly accessible, no authentication required
Route::prefix('api-docs')->name('api.docs.')->group(function () {
    Route::get('/', [ApiDocController::class, 'index'])->name('ui');
    Route::get('spec', [ApiDocController::class, 'spec'])->name('spec');
});

// Broadcasting auth is handled by Laravel's built-in BroadcastServiceProvider.
// The Reverb module was removed; no custom broadcasting routes needed.

Route::middleware('auth')->get('/panel/settings', [SettingsPanelController::class, 'index'])->name('settings.panel');

Route::middleware('auth')->prefix('panel/settings')->name('settings.')->group(function () {
    Route::resource('alerts', AlertThresholdController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::post('alerts/{alert}/toggle', [AlertThresholdController::class, 'toggle'])->name('alerts.toggle');
});

Route::post('/contact/send', [ContactController::class, 'send'])
    ->name('contact.send')
    ->middleware('throttle:5,1');

Route::middleware(['auth', 'throttle:global-search'])
    ->get('/panel/search', GlobalSearchController::class)
    ->name('search.global');

Route::middleware('auth')->prefix('panel/import')->name('import.')->group(function () {
    Route::get('/{type}', [ImportController::class, 'show'])->name('show');
    Route::post('/{type}', [ImportController::class, 'store'])->name('store');
    Route::get('/{type}/template', [ImportController::class, 'template'])->name('template');
});
