<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskTranslate\Http\Controllers\Managers\DetectLanguageController;
use Modules\HelpdeskTranslate\Http\Controllers\Managers\TranslateController;
use Modules\HelpdeskTranslate\Http\Controllers\Managers\TranslateItemController;

/*
|--------------------------------------------------------------------------
| HelpdeskTranslate manager routes
|--------------------------------------------------------------------------
| Mounted by HelpdeskTranslateServiceProvider with prefix `panel/helpdesk`
| and name `manager.helpdesk.` to preserve backwards compatibility with
| URLs and route names already used by the Helpdesk frontend.
*/

Route::post('/translate', TranslateController::class)
    ->middleware('throttle:60,1')
    ->name('translate');

Route::post('/conversations/items/{item}/translate', TranslateItemController::class)
    ->middleware('throttle:60,1')
    ->name('conversations.items.translate');

Route::post('/detect-language', DetectLanguageController::class)
    ->middleware('throttle:60,1')
    ->name('detect-language');
