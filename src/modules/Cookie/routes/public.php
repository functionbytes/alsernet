<?php

use Illuminate\Support\Facades\Route;
use Modules\Cookie\Http\Controllers\CookieConsentController;

Route::post('consent', [CookieConsentController::class, 'store'])->middleware('throttle:10,1')->name('consent.store');
Route::get('consent/status', [CookieConsentController::class, 'status'])->middleware('throttle:60,1')->name('consent.status');
// Cookie policy page is now served from the Pages module (slug: cookie/policy)
