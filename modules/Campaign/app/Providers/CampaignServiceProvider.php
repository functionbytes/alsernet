<?php

namespace Modules\Campaign\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Campaign\Console\Commands\CleanupLogsCommand;
use Modules\Campaign\Console\Commands\DemoCommand;
use Modules\Campaign\Console\Commands\DispatchAutomationJobsCommand;
use Modules\Campaign\Console\Commands\ExecuteScheduledCampaignsCommand;
use Modules\Campaign\Console\Commands\InstallCommand;
use Modules\Theme\Services\NavService;
use Nwidart\Modules\Facades\Module;

class CampaignServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Campaign';

    protected string $moduleNameLower = 'campaign';

    public function register(): void
    {
        $this->mergeConfigFrom(
            module_path($this->moduleName, 'config/campaign.php'),
            'campaign'
        );
    }

    public function boot(): void
    {
        if (Module::find($this->moduleName)?->isDisabled()) {
            return;
        }

        $this->registerConfig();
        $this->registerMigrations();
        $this->registerViews();
        $this->registerRoutes();
        $this->registerMenus();
        $this->registerSchedules();
        $this->registerCommands();
    }

    protected function registerConfig(): void
    {
        $this->publishes([
            module_path($this->moduleName, 'config/campaign.php') => config_path('campaign.php'),
        ], 'config');
    }

    protected function registerMigrations(): void
    {
        $this->loadMigrationsFrom(module_path($this->moduleName, 'database/migrations'));
    }

    protected function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->moduleNameLower);
        $sourcePath = module_path($this->moduleName, 'resources/views');
        $legacyViewsPath = module_path($this->moduleName, 'views');

        $this->publishes([
            $sourcePath => $viewPath,
        ], ['views', $this->moduleNameLower.'-module-views']);

        $paths = $this->getPublishableViewPaths();
        $paths[] = $sourcePath;
        if (is_dir($legacyViewsPath)) {
            // Compat con la carpeta `views/` heredada de Acelle
            $paths[] = $legacyViewsPath;
        }

        $this->loadViewsFrom($paths, $this->moduleNameLower);
    }

    protected function registerRoutes(): void
    {
        // Manager (panel admin)
        $managersPath = module_path($this->moduleName, 'routes/managers.php');
        if (file_exists($managersPath)) {
            Route::middleware(['web', 'auth', 'role:super-admin|super-settings'])
                ->prefix('panel/campaign')
                ->group($managersPath);
        }

        // API (Sanctum + throttle 60/min global; endpoints sensibles sobreescriben)
        $apiPath = module_path($this->moduleName, 'routes/api.php');
        if (file_exists($apiPath)) {
            Route::middleware(['api', 'auth:sanctum', 'throttle:60,1'])
                ->prefix('api/campaign')
                ->name('api.campaign.')
                ->group($apiPath);
        }

        // Público (open pixel, click redirect, unsubscribe — sin auth)
        $webPath = module_path($this->moduleName, 'routes/web.php');
        if (file_exists($webPath)) {
            Route::middleware('web')
                ->prefix('campaign')
                ->group($webPath);
        }
    }

    protected function registerMenus(): void
    {
        if (! class_exists(NavService::class)) {
            return;
        }

        NavService::registerSidebar('campaigns', [
            'title' => 'Campañas',
            'permission' => 'campaigns.view.all',
            'items' => [
                ['label' => 'Campañas', 'route' => 'manager.campaigns.index', 'icon' => 'fas fa-bullhorn', 'permission' => 'campaigns.view.all'],
                ['label' => 'Listas', 'route' => 'manager.campaigns.maillists.index', 'icon' => 'fas fa-list', 'permission' => 'campaigns.maillists.view'],
                ['label' => 'Suscriptores', 'route' => 'manager.campaigns.subscribers.index', 'icon' => 'fas fa-users', 'permission' => 'campaigns.maillists.view'],
                ['label' => 'Segmentos', 'route' => 'manager.campaigns.segments.index', 'icon' => 'fas fa-filter', 'permission' => 'campaigns.maillists.view'],
                ['label' => 'Plantillas', 'route' => 'manager.campaigns.templates.index', 'icon' => 'fas fa-file-lines', 'permission' => 'campaigns.templates.view'],
                ['label' => 'Automatizaciones', 'route' => 'manager.campaigns.automations.index', 'icon' => 'fas fa-cogs', 'permission' => 'campaigns.automations.view'],
            ],
        ]);
    }

    protected function registerSchedules(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);

            $schedule->command('campaign:execute-scheduled')
                ->name('campaign:execute_scheduled')
                ->everyMinute()
                ->withoutOverlapping(5);

            $schedule->command('campaign:dispatch-automations')
                ->name('campaign:dispatch_automations')
                ->everyFiveMinutes()
                ->withoutOverlapping(5);

            // Cleanup semanal de logs > 180 días.
            $schedule->command('campaign:cleanup --older-than=180')
                ->name('campaign:cleanup_logs')
                ->weekly()
                ->sundays()
                ->at('03:00');
        });
    }

    protected function registerCommands(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            ExecuteScheduledCampaignsCommand::class,
            DispatchAutomationJobsCommand::class,
            InstallCommand::class,
            CleanupLogsCommand::class,
            DemoCommand::class,
        ]);
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (config('view.paths') ?? [] as $path) {
            if (is_dir($path.'/modules/'.$this->moduleNameLower)) {
                $paths[] = $path.'/modules/'.$this->moduleNameLower;
            }
        }

        return $paths;
    }

    public function provides(): array
    {
        return [];
    }
}
