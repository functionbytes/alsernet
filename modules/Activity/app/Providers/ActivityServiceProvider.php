<?php

namespace Modules\Activity\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Activity\Console\Commands\PruneActivityLogsCommand;
use Modules\Theme\Services\NavService;

class ActivityServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerConfigurations();
        $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'activity');
        $this->registerMenus();
        $this->registerCommands();
    }

    protected function registerCommands(): void
    {
        $this->commands([
            PruneActivityLogsCommand::class,
        ]);
    }

    protected function registerConfigurations(): void
    {
        $this->publishes([
            __DIR__.'/../../config/activity.php' => config_path('activity.php'),
        ], 'config');
    }

    /**
     * Registrar menús del módulo Activity
     */
    protected function registerMenus(): void
    {
        // Sidebar con los items del módulo
        NavService::registerSidebar('settings', [
            'title' => 'Historial de actividad',
            'items' => [
                ['label' => 'Registro de cambios', 'route' => 'activity.logs'],
                ['label' => 'Auditoría', 'route' => 'activity.audit'],
            ],
        ]);
    }
}
