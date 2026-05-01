<?php

use Illuminate\Support\Facades\Route;
use Modules\Helpdesk\Http\Controllers\Pages\CsatController;
use Modules\Helpdesk\Http\Controllers\Pages\DemoController;
use Modules\Helpdesk\Http\Controllers\Pages\WidgetController;
use Modules\Helpdesk\Http\Controllers\Pages\WidgetScriptController;

/*
|--------------------------------------------------------------------------
| Helpdesk widget public routes
|--------------------------------------------------------------------------
| Mounted by Modules\Helpdesk\Providers\RouteServiceProvider with the
| 'web' middleware group. Coexists with Chat module's /lc/widget routes.
*/

// Demo / install instructions page for tenants
Route::get('/pages/helpdesk-widget/{websiteToken?}', [DemoController::class, 'widget'])
    ->name('helpdesk.pages.widget');

// Embed loader script (the visitor's site embeds this URL via <script>)
Route::prefix('widget/helpdesk')
    ->name('helpdesk.widget-script.')
    ->group(function () {
        Route::get('/script/{websiteToken}', [WidgetScriptController::class, 'script'])->name('script');
        Route::get('/config/{websiteToken}', [WidgetScriptController::class, 'config'])->name('config');
    });

// CSAT survey (public, link sent to customer when conversation closes)
Route::prefix('helpdesk/csat')
    ->name('helpdesk.csat.')
    ->middleware('throttle:60,1')
    ->group(function () {
        Route::get('/{token}', [CsatController::class, 'show'])->name('show');
        Route::post('/{token}', [CsatController::class, 'submit'])->name('submit');
    });

// SPA inline mountpoint (full-page render of the widget React app)
Route::prefix('hd')
    ->name('helpdesk.widget.spa.')
    ->group(function () {
        Route::get('/widget', [WidgetController::class, 'index'])->name('index');
        Route::get('/widget/{any?}', [WidgetController::class, 'index'])
            ->where('any', '.*')
            ->name('catchall');
    });
