<?php

namespace Modules\Helpdesk\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;
use Modules\Helpdesk\Http\Controllers\Managers\AgentsController;
use Modules\Helpdesk\Http\Controllers\Managers\ConversationsController;

class RouteServiceProvider extends ServiceProvider
{
    protected string $name = 'Helpdesk';

    public function map(): void
    {
        $this->mapApiRoutes();
        $this->mapWebRoutes();
        $this->mapWebhookRoutes();
    }

    protected function mapWebhookRoutes(): void
    {
        Route::middleware('api')
            ->prefix('api/helpdesk')
            ->group(module_path($this->name, 'routes/webhooks.php'));
    }

    protected function mapWebRoutes(): void
    {
        // Settings routes: panel/settings/helpdesk/*
        Route::middleware(['web', 'auth', 'role:super-admin|super-settings'])
            ->prefix('panel/settings/helpdesk')
            ->name('settings.helpdesk.')
            ->group(module_path($this->name, 'routes/settings.php'));

        // Legacy redirects 301: panel/helpdesk/settings/* → panel/settings/helpdesk/*
        Route::middleware('web')->group(function () {
            Route::redirect('panel/helpdesk/settings/livechat', 'panel/settings/helpdesk/livechat', 301);
            Route::redirect('panel/helpdesk/settings/ai', 'panel/settings/helpdesk/ai', 301);
            Route::redirect('panel/helpdesk/settings/uploading', 'panel/settings/helpdesk/uploading', 301);
            Route::redirect('panel/helpdesk/settings/social-integrations', 'panel/settings/helpdesk/social-integrations', 301);
            Route::redirect('panel/helpdesk/settings/webhooks', 'panel/settings/helpdesk/webhooks', 301);
            Route::redirect('panel/helpdesk/settings/schedule', 'panel/settings/helpdesk/schedule', 301);
            Route::redirect('panel/helpdesk/settings/tickets', 'panel/settings/helpdesk/tickets', 301);
            Route::redirect('panel/helpdesk/settings/tickets/attributes', 'panel/settings/helpdesk/attributes', 301);
            Route::redirect('panel/helpdesk/settings/tickets/tags', 'panel/settings/helpdesk/tags', 301);
            Route::redirect('panel/helpdesk/settings/tickets/statuses', 'panel/settings/helpdesk/statuses', 301);
            Route::redirect('panel/helpdesk/settings/tickets/team/members', 'panel/settings/helpdesk/team/members', 301);
            Route::redirect('panel/helpdesk/settings/tickets/team/groups', 'panel/settings/helpdesk/team/groups', 301);

            Route::redirect('panel/settings/helpdesk/tickets/attributes', 'panel/settings/helpdesk/attributes', 301);
            Route::redirect('panel/settings/helpdesk/tickets/tags', 'panel/settings/helpdesk/tags', 301);
            Route::redirect('panel/settings/helpdesk/tickets/statuses', 'panel/settings/helpdesk/statuses', 301);
            Route::redirect('panel/settings/helpdesk/tickets/views', 'panel/settings/helpdesk/views', 301);
            Route::redirect('panel/settings/helpdesk/tickets/team/members', 'panel/settings/helpdesk/team/members', 301);
            Route::redirect('panel/settings/helpdesk/tickets/team/groups', 'panel/settings/helpdesk/team/groups', 301);
        });

        // Manager routes (admin interface for helpdesk)
        Route::middleware(['web', 'auth', 'role:super-admin|super-settings'])
            ->prefix('panel/helpdesk')
            ->group(module_path($this->name, 'routes/managers.php'));

        // Inbox v4 (auth only, no role restriction)
        Route::middleware(['web', 'auth'])
            ->prefix('panel/helpdesk')
            ->group(function () {
                Route::view('/inbox', 'helpdesk::managers.inbox.index')
                    ->name('manager.helpdesk.inbox.index');

                Route::get('/api/agents-autocomplete', [AgentsController::class, 'search'])
                    ->middleware('throttle:120,1')
                    ->name('manager.helpdesk.api.agents.autocomplete');

                Route::get('/api/attachment-download', [ConversationsController::class, 'downloadAttachment'])
                    ->middleware('throttle:60,1')
                    ->name('manager.helpdesk.api.attachment-download');
            });

        Route::middleware(['web'])
            ->group(module_path($this->name, 'routes/public.php'));

        Route::middleware(['web'])
            ->group(module_path($this->name, 'routes/portal.php'));
    }

    protected function mapApiRoutes(): void
    {
        Route::middleware(['api', 'auth:sanctum', 'throttle:60,1'])
            ->prefix('api/v1/helpdesk')
            ->name('api.v1.helpdesk.')
            ->group(module_path($this->name, 'routes/api.php'));
    }
}
