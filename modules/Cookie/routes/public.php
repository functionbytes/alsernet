<?php

use Illuminate\Support\Facades\Route;
use Modules\Cookie\Http\Controllers\CookieConsentController;

Route::post('consent', [CookieConsentController::class, 'store'])->middleware('throttle:10,1')->name('consent.store');
Route::get('consent/status', [CookieConsentController::class, 'status'])->name('consent.status');
