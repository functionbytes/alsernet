<?php

use Illuminate\Support\Facades\Route;
use Modules\Captcha\Http\Controllers\Settings\CaptchaSettingController;

Route::middleware(['web', 'auth'])
    ->prefix('panel/setting/captcha')
    ->name('captcha.settings')
    ->group(function () {
        Route::get('/', [CaptchaSettingController::class, 'edit']);
        Route::put('/', [CaptchaSettingController::class, 'update'])->name('.update');
    });
