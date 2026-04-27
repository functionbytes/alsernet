<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Captcha\Http\Controllers\Settings\CaptchaSettingController;

Route::middleware(['web', 'auth'])
    ->prefix('panel/settings/captcha')
    ->name('settings.captcha.')
    ->group(function () {
        Route::get('/', [CaptchaSettingController::class, 'edit'])->name('edit');
        Route::put('/', [CaptchaSettingController::class, 'update'])->name('update');
    });

// Legacy redirects: panel/setting/captcha → panel/settings/captcha
Route::middleware(['web'])->group(function () {
    Route::redirect('panel/setting/captcha/{any}', 'panel/settings/captcha/{any}', 301)->where('any', '.*');
    Route::redirect('panel/setting/captcha', 'panel/settings/captcha', 301);
});
