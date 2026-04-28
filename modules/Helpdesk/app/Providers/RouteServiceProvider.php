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
        // Helpdesk settings routes
        Route::middleware(['web', 'auth', 'role:super-admin|super-settings'])
            ->prefix('panel/helpdesk/settings')
            ->name('helpdesk.backups.')
            ->group(module_path($this->name, 'routes/web.php'));

        // Manager routes (admin interface for helpdesk)
        Route::middleware(['web', 'auth', 'role:super-admin|super-settings'])
            ->prefix('panel/helpdesk')
            ->group(module_path($this->name, 'routes/managers.php'));

        // Bandeja v4 — Diseño nuevo (acceso solo con auth, sin role restrictivo)
        Route::middleware(['web', 'auth'])
            ->prefix('panel/helpdesk')
            ->group(function () {
                Route::view('/bandeja', 'helpdesk::managers.helpdesk.bandeja.index')
                    ->name('manager.helpdesk.bandeja.index');
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
