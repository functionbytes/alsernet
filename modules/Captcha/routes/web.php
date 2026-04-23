<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Captcha\Http\Controllers\Settings\CaptchaSettingController;

Route::middleware(['web', 'auth', 'throttle:10,1'])
    ->prefix('panel/setting/captcha')
    ->name('captcha.settings')
    ->group(function () {
        Route::get('/', [CaptchaSettingController::class, 'edit']);
        Route::put('/', [CaptchaSettingController::class, 'update'])->name('.update');
    });
