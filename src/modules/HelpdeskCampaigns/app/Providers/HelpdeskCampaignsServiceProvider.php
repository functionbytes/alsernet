<?php

namespace Modules\HelpdeskCampaigns\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\HelpdeskCampaigns\Jobs\CleanupOldImpressionsJob;
use Modules\HelpdeskCampaigns\Jobs\EndExpiredCampaignsJob;
use Modules\HelpdeskCampaigns\Jobs\PublishScheduledCampaignsJob;
use Modules\HelpdeskCampaigns\Models\Campaign;
use Modules\HelpdeskCampaigns\Observers\CampaignObserver;
use Modules\HelpdeskCampaigns\Policies\CampaignPolicy;
use Modules\Theme\Services\NavService;
use Nwidart\Modules\Facades\Module;

class HelpdeskCampaignsServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'HelpdeskCampaigns';

    protected string $moduleNameLower = 'helpdeskcampaigns';

    public function boot(): void
    {
        if (Module::find($this->moduleName)?->isDisabled()) {
            return;
        }

        $this->loadMigrationsFrom(module_path($this->moduleName, 'database/migrations'));
        $this->registerConfig();
        $this->registerTranslations();
        $this->registerViews();
        $this->registerRoutes();
        $this->registerMenus();
        $this->registerObservers();
        $this->registerSchedules();
    }

    protected function registerObservers(): void
    {
        if (class_exists(Campaign::class)) {
            Campaign::observe(CampaignObserver::class);
        }
    }

    protected function registerSchedules(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);

            $schedule->job(new PublishScheduledCampaignsJob)
                ->everyMinute()
                ->withoutOverlapping()
                ->onOneServer();

            $schedule->job(new EndExpiredCampaignsJob)
                ->everyMinute()
                ->withoutOverlapping()
                ->onOneServer();

            $schedule->job(new CleanupOldImpressionsJob)
                ->daily()
                ->at('03:30')
                ->onOneServer();
        });
    }

    protected function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/'.$this->moduleNameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->moduleNameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(module_path($this->moduleName, 'lang'), $this->moduleNameLower);
            $this->loadJsonTranslationsFrom(module_path($this->moduleName, 'lang'));
        }
    }

    protected function registerMenus(): void
    {
        if (! helpdesk_campaigns_enabled()) {
            return;
        }

        NavService::registerSidebar('helpdesk', [
            'title' => 'Campañas',
            'items' => [
                [
                    'label' => 'Listado de campañas',
                    'route' => 'helpdesk.campaigns.index',
                    'icon' => 'fas fa-bullhorn',
                    'permission' => 'helpdesk.campaigns.view',
                ],
                [
                    'label' => 'Plantillas',
                    'route' => 'helpdesk.campaigns.templates',
                    'icon' => 'fas fa-file-lines',
                    'permission' => 'helpdesk.campaigns.manage',
                ],
            ],
        ]);
    }

    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->registerPolicies();
    }

    protected function registerPolicies(): void
    {
        if (class_exists(Campaign::class)) {
            Gate::policy(Campaign::class, CampaignPolicy::class);
        }
    }

    protected function registerConfig(): void
    {
        $configPath = module_path($this->moduleName, 'config/config.php');

        if (! file_exists($configPath)) {
            return;
        }

        $this->publishes([$configPath => config_path($this->moduleNameLower.'.php')], 'config');
        $this->mergeConfigFrom($configPath, $this->moduleNameLower);
    }

    protected function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->moduleNameLower);
        $sourcePath = module_path($this->moduleName, 'resources/views');

        $this->publishes([
            $sourcePath => $viewPath,
        ], ['views', $this->moduleNameLower.'-module-views']);

        $this->loadViewsFrom(
            array_merge($this->getPublishableViewPaths(), [$sourcePath]),
            $this->moduleNameLower
        );
    }

    protected function registerRoutes(): void
    {
        $this->loadManagerRoutes();
        $this->loadApiRoutes();
        $this->loadPortalRoutes();
    }

    protected function loadManagerRoutes(): void
    {
        $path = module_path($this->moduleName, 'routes/managers.php');

        if (! file_exists($path)) {
            return;
        }

        Route::middleware(['web', 'auth', 'role:super-admin|super-settings'])
            ->prefix('panel/helpdesk')
            ->group($path);
    }

    protected function loadApiRoutes(): void
    {
        $path = module_path($this->moduleName, 'routes/api.php');

        if (! file_exists($path)) {
            return;
        }

        Route::middleware(['api', 'auth:sanctum', 'throttle:60,1'])
            ->prefix('api/v1/helpdesk/campaigns')
            ->name('api.v1.helpdesk.campaigns.')
            ->group($path);
    }

    protected function loadPortalRoutes(): void
    {
        $path = module_path($this->moduleName, 'routes/portal.php');

        if (! file_exists($path)) {
            return;
        }

        Route::middleware(['api', 'throttle:120,1'])
            ->prefix('helpdesk/campaigns/track')
            ->name('helpdesk-campaigns.track.')
            ->group($path);
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (config('view.paths') as $path) {
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
