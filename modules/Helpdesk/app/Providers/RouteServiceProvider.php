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
        Route::middleware('web')
            ->group(module_path($this->name, 'routes/webhooks.php'));
    }

    protected function mapWebRoutes(): void
    {
        // Customer self-service portal (no auth — customers authenticate themselves)
        Route::middleware('web')
            ->prefix('portal')
            ->name('portal.')
            ->group(module_path($this->name, 'routes/portal.php'));

        // Helpdesk settings routes
        Route::middleware(['web', 'auth', 'role:super-admin'])
            ->prefix('panel/settings/helpdesk')
            ->name('helpdesk.backups.')
            ->group(module_path($this->name, 'routes/web.php'));

        // Manager routes (admin interface for helpdesk)
        Route::middleware(['web', 'auth', 'role:super-admin'])
            ->prefix('panel/manager/helpdesk')
            ->name('manager.helpdesk.')
            ->group(module_path($this->name, 'routes/managers.php'));

        // Agent routes
        Route::middleware(['web', 'auth', 'role:helpdesk-agent|super-admin'])
            ->prefix('panel/helpdesk/agent')
            ->name('agent.helpdesk.')
            ->group(module_path($this->name, 'routes/agents.php'));
    }

    protected function mapApiRoutes(): void
    {
        Route::middleware(['api', 'auth:sanctum', 'throttle:60,1'])
            ->prefix('api/helpdesk')
            ->name('api.helpdesk.')
            ->group(module_path($this->name, 'routes/api.php'));

        // Widget/Live chat routes (public — no auth required)
        Route::middleware(['api', 'throttle:120,1'])
            ->prefix('lc/api')
            ->name('widget.')
            ->group(module_path($this->name, 'routes/widget.php'));
    }
}
