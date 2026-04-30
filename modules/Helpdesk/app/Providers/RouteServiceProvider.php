<?php

namespace Modules\Helpdesk\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    protected string $name = 'Helpdesk';

    public function boot(): void
    {
        parent::boot();
    }

    public function map(): void
    {
        $this->mapApiRoutes();
        $this->mapWebRoutes();
        $this->mapWebhookRoutes();
    }

    protected function mapWebhookRoutes(): void
    {
        // Public routes — no auth, no CSRF (already excluded via VerifyCsrfToken::$except)
        Route::middleware('api')
            ->group(module_path($this->name, 'routes/webhooks.php'));
    }

    protected function mapWebRoutes(): void
    {
        // Helpdesk settings routes (Patrón A: panel/settings/{module})
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

            // Legacy nested under settings/helpdesk/tickets/* → settings/helpdesk/* (post-rename)
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

        // Inbox v4 — Diseño nuevo (acceso solo con auth, sin role restrictivo)
        Route::middleware(['web', 'auth'])
            ->prefix('panel/helpdesk')
            ->group(function () {
                Route::view('/inbox', 'helpdesk::managers.inbox.index')
                    ->name('manager.helpdesk.inbox.index');
            });

        // Portal + Agent routes moved to HelpdeskTickets module.

        // Public routes (feedback/CSAT survey — no auth)
        Route::middleware(['web'])
            ->group(module_path($this->name, 'routes/public.php'));
    }

    protected function mapApiRoutes(): void
    {
        Route::middleware(['api', 'auth:sanctum', 'throttle:60,1'])
            ->prefix('api/v1/helpdesk')
            ->name('api.v1.helpdesk.')
            ->group(module_path($this->name, 'routes/api.php'));

        // Widget/Live chat routes (public — no auth required)
        Route::middleware(['api', 'throttle:120,1'])
            ->prefix('lc/api')
            ->name('widget.')
            ->group(module_path($this->name, 'routes/widget.php'));
    }
}
