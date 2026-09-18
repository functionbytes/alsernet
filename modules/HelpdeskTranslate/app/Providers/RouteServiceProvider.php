<?php

namespace Modules\HelpdeskTranslate\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    protected string $name = 'HelpdeskTranslate';

    public function boot(): void
    {
        $this->mapManagerRoutes();
        $this->mapSettingsRoutes();
    }

    /**
     * Manager routes are registered under `panel/helpdesk` with name prefix
     * `manager.helpdesk.` so the URLs and route names stay backwards
     * compatible with the existing Helpdesk frontend (Blade views, jQuery).
     */
    protected function mapManagerRoutes(): void
    {
        Route::middleware(['web', 'auth'])
            ->prefix('panel/helpdesk')
            ->name('manager.helpdesk.')
            ->group(module_path($this->name, '/routes/managers.php'));
    }

    /**
     * Settings routes live under `panel/settings/helpdesk-translate` so they
     * sit alongside the rest of the helpdesk settings menu entries.
     */
    protected function mapSettingsRoutes(): void
    {
        Route::middleware(['web', 'auth'])
            ->prefix('panel/settings/helpdesk-translate')
            ->name('settings.helpdesk-translate.')
            ->group(module_path($this->name, '/routes/settings.php'));
    }
}
