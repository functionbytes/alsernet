<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskTranslate\Http\Controllers\Settings\TranslateSettingsController;

/*
|--------------------------------------------------------------------------
| HelpdeskTranslate settings routes
|--------------------------------------------------------------------------
| Mounted by HelpdeskTranslateServiceProvider with prefix
| `panel/settings/helpdesk-translate` and name `settings.helpdesk-translate.`
| so it slots in alongside other helpdesk module settings pages.
*/

Route::get('/', [TranslateSettingsController::class, 'index'])->name('index');
Route::put('/', [TranslateSettingsController::class, 'update'])->name('update');
Route::get('/usage', [TranslateSettingsController::class, 'usage'])
    ->middleware('throttle:30,1')
    ->name('usage');
Route::get('/usage/export', [TranslateSettingsController::class, 'usageExport'])
    ->middleware('throttle:10,1')
    ->name('usage.export');
Route::post('/test', [TranslateSettingsController::class, 'test'])
    ->middleware('throttle:5,1')
    ->name('test');
Route::delete('/cache', [TranslateSettingsController::class, 'clearCache'])
    ->middleware('throttle:5,1')
    ->name('cache.clear');
