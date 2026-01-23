<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskChat\Http\Controllers\DemoController;
use Modules\HelpdeskChat\Http\Controllers\WidgetApiController;
use Modules\HelpdeskChat\Http\Controllers\WidgetScriptController;

/*
|--------------------------------------------------------------------------
| Chat Module - Web Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['web', 'module:HelpdeskChat'])->group(function () {

    // =============================================================================
    // ADMIN AREA - /admin/helpdesk/*
    // =============================================================================
    Route::middleware(['auth', 'role:super-admin|admin'])
        ->prefix('admin/helpdesk')
        ->name('admin.helpdesk.')
        ->group(__DIR__.'/admin.php');

    // =============================================================================
    // CALLCENTER AREA - /callcenter/helpdesk/*
    // =============================================================================
    Route::middleware(['auth', 'role:admin|callcenter'])
        ->prefix('callcenter/helpdesk')
        ->name('callcenter.helpdesk.')
        ->group(__DIR__.'/callcenter.php');

    // =============================================================================
    // CUSTOMER AREA - /customer/helpdesk/*
    // =============================================================================
    Route::middleware(['auth', 'role:customer'])
        ->prefix('customer/helpdesk')
        ->name('customer.helpdesk.')
        ->group(__DIR__.'/customer.php');

    // =============================================================================
    // PUBLIC ROUTES - Widget and Public APIs
    // =============================================================================
    Route::group([], function () {

        // Widget Demo Page (public)
        Route::get('/demo/widget/{websiteToken?}', [DemoController::class, 'widget'])->name('demo.widget');

        // Widget SDK Routes (public)
        Route::prefix('widget')->name('widget.')->group(function () {
            // Serve widget JavaScript SDK
            Route::get('/script/{websiteToken}', [WidgetScriptController::class, 'script'])->name('script');
            Route::get('/config/{websiteToken}', [WidgetScriptController::class, 'config'])->name('config');
        });

        // Widget API Routes (public AJAX endpoints)
        Route::prefix('api/widget/{websiteToken}')->name('api.widget.')->group(function () {
            Route::post('/init', [WidgetApiController::class, 'initConversation'])->name('init');
            Route::post('/send', [WidgetApiController::class, 'sendMessage'])->name('send');
            Route::get('/messages/{conversationId}', [WidgetApiController::class, 'getMessages'])->name('messages');
            Route::get('/availability', [WidgetApiController::class, 'checkAvailability'])->name('availability');
        });

        // LiveChat Widget - Public route (no authentication required)
        Route::prefix('lc')->name('lc.')->group(function () {
            Route::get('/widget', [Modules\HelpdeskChat\Http\Controllers\Helpdesk\WidgetController::class, 'index'])->name('widget');
            Route::get('/launcher-demo', [Modules\HelpdeskChat\Http\Controllers\Helpdesk\WidgetController::class, 'launcherDemo'])->name('launcher-demo');
            Route::get('/api/settings', [Modules\HelpdeskChat\Http\Controllers\Helpdesk\WidgetController::class, 'settings'])->name('widget.settings');
            Route::get('/api/helpcenter', [Modules\HelpdeskChat\Http\Controllers\Admin\Helpcenter\HelpCenterController::class, 'apiWidget'])->name('widget.helpcenter');
            Route::get('/api/helpcenter/articles/{id}', [Modules\HelpdeskChat\Http\Controllers\Admin\Helpcenter\HelpCenterController::class, 'apiArticle'])->name('widget.helpcenter.article');

            // Widget Conversations API - Public (customer-facing)
            Route::post('/api/conversation', [Modules\HelpdeskChat\Http\Controllers\Api\WidgetConversationController::class, 'store'])->name('api.conversation.store');
            Route::get('/api/conversation/{id}', [Modules\HelpdeskChat\Http\Controllers\Api\WidgetConversationController::class, 'show'])->name('api.conversation.show');
            Route::post('/api/conversation/{id}/messages', [Modules\HelpdeskChat\Http\Controllers\Api\WidgetConversationController::class, 'sendMessage'])->name('api.conversation.messages.send');
            Route::get('/api/conversation/{id}/messages', [Modules\HelpdeskChat\Http\Controllers\Api\WidgetConversationController::class, 'getMessages'])->name('api.conversation.messages.index');
            Route::post('/api/conversation/{id}/close', [Modules\HelpdeskChat\Http\Controllers\Api\WidgetConversationController::class, 'close'])->name('api.conversation.close');

            // Catch-all route for React Router (BrowserRouter) - Must be last
            // This allows client-side routing for /lc/widget/*, /lc/widget/conversation, /lc/widget/help, etc.
            Route::get('/widget/{any?}', [Modules\HelpdeskChat\Http\Controllers\Helpdesk\WidgetController::class, 'index'])
                ->where('any', '.*')
                ->name('widget.catchall');
        });

        // Alternative route alias for launcher demo (livechat prefix)
        Route::get('/livechat/launcher-demo', [Modules\HelpdeskChat\Http\Controllers\Helpdesk\WidgetController::class, 'launcherDemo'])->name('livechat.launcher-demo');

    });

});
