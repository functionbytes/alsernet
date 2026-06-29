<?php

namespace Modules\Activity\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Activity\Console\Commands\PruneActivityLogsCommand;
use Modules\Activity\Policies\ActivityPolicy;
use Modules\Theme\Services\NavService;
use Nwidart\Modules\Facades\Module;
use Spatie\Activitylog\Models\Activity;

class ActivityServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (Module::find('Activity')?->isDisabled()) {
            return;
        }

        $this->registerConfigurations();
        $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'activity');
        $this->registerMenus();
        $this->registerCommands();

        Gate::policy(Activity::class, ActivityPolicy::class);
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
