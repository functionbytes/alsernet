<?php

use App\Http\Controllers\DashboardCustomizerController;
use App\Http\Controllers\PushSubscriptionController;
use Illuminate\Support\Facades\Route;
use Modules\Forms\Http\Controllers\ContactController;
use Modules\Health\Http\Controllers\AlertThresholdController;
use Modules\System\Http\Controllers\ApiDocController;
use Modules\System\Http\Controllers\GlobalSearchController;
use Modules\System\Http\Controllers\ImportController;
use Modules\System\Http\Controllers\Settings\SettingsPanelController;

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

Route::middleware(['web', 'auth'])->prefix('panel/push')->group(function () {
    Route::post('subscribe', [PushSubscriptionController::class, 'subscribe']);
    Route::post('unsubscribe', [PushSubscriptionController::class, 'unsubscribe']);
});

Route::middleware(['web', 'auth'])->prefix('panel/dashboard')->name('dashboard.')->group(function () {
    Route::get('customize', [DashboardCustomizerController::class, 'index'])->name('customize');
    Route::post('customize', [DashboardCustomizerController::class, 'save'])->name('customize.save');
    Route::post('customize/reset', [DashboardCustomizerController::class, 'reset'])->name('customize.reset');
    Route::get('widget-data/{source}', [DashboardCustomizerController::class, 'widgetData'])->name('widget-data');
});
