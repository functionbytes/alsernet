<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskLivechat\Http\Controllers\Pages\DemoController;
use Modules\HelpdeskLivechat\Http\Controllers\Pages\WidgetAssetController;
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

// Widget bundle loader alias — the URL shown in the installation instructions UI
Route::get('/hd/widget-loader.js', [WidgetAssetController::class, 'loader'])
    ->name('helpdesk-livechat.widget-loader');

// SPA inline mountpoint (full-page render of the widget React app)
Route::prefix('hd')
    ->name('helpdesk-livechat.widget.spa.')
    ->group(function () {
        Route::get('/widget', [WidgetController::class, 'index'])->name('index');
        Route::get('/widget/{any?}', [WidgetController::class, 'index'])
            ->where('any', '.*')
            ->name('catchall');
    });

// Widget bundle assets with CORS headers — needed when the host site embeds
// the widget on a different origin (or file://) and uses <script type="module">.
// These routes go through Laravel because Herd/nginx serves static files
// without CORS headers, blocking cross-origin module loads.
Route::get('/hd/assets/embed.js', [WidgetAssetController::class, 'embed'])
    ->name('helpdesk-livechat.assets.embed');

Route::get('/hd/assets/main.js', [WidgetAssetController::class, 'bundle'])
    ->name('helpdesk-livechat.assets.bundle');

Route::get('/hd/assets/main.css', [WidgetAssetController::class, 'style'])
    ->name('helpdesk-livechat.assets.style');

// Lazy-loaded chunks (rrweb, etc.) — Vite resolves them relative to main.js,
// so we mirror the chunks/ directory under /hd/assets/ with CORS headers.
Route::get('/hd/assets/chunks/{file}', [WidgetAssetController::class, 'chunk'])
    ->name('helpdesk-livechat.assets.chunk');
