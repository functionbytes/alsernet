<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskLivechat\Http\Controllers\Pages\DemoController;
use Modules\HelpdeskLivechat\Http\Controllers\Pages\WidgetController;
use Modules\HelpdeskLivechat\Http\Controllers\Pages\WidgetScriptController;

/*
|--------------------------------------------------------------------------
| Live Chat widget public routes
|--------------------------------------------------------------------------
| Mounted by Modules\HelpdeskLivechat\Providers\HelpdeskLivechatServiceProvider
| with the 'web' middleware group. Public — no auth required.
*/

// Demo / install instructions page for tenants
Route::get('/pages/helpdesk-widget/{websiteToken?}', [DemoController::class, 'widget'])
    ->name('helpdesk-livechat.pages.widget');

// Embed loader script (the visitor's site embeds this URL via <script>)
Route::prefix('widget/helpdesk')
    ->name('helpdesk-livechat.widget-script.')
    ->group(function () {
        Route::get('/script/{websiteToken}', [WidgetScriptController::class, 'script'])->name('script');
        Route::get('/config/{websiteToken}', [WidgetScriptController::class, 'config'])->name('config');
    });

// SPA inline mountpoint (full-page render of the widget React app)
Route::prefix('hd')
    ->name('helpdesk-livechat.widget.spa.')
    ->group(function () {
        Route::get('/widget', [WidgetController::class, 'index'])->name('index');
        Route::get('/widget/{any?}', [WidgetController::class, 'index'])
            ->where('any', '.*')
            ->name('catchall');
    });
