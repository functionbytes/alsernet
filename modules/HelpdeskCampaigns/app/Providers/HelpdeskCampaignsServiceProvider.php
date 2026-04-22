<?php

namespace Modules\HelpdeskCampaigns\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\HelpdeskCampaigns\Models\Campaign;
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

        $this->registerConfig();
        $this->registerViews();
        $this->registerRoutes();
        $this->registerMenus();
    }

    protected function registerMenus(): void
    {
        NavService::registerSidebar('helpdesk', [
            'title' => 'Campañas',
            'items' => [
                [
                    'label' => 'Listado de campañas',
                    'route' => 'manager.helpdesk-campaigns.index',
                    'icon' => 'fas fa-bullhorn',
                    'permission' => 'helpdesk.campaigns.view',
                ],
                [
                    'label' => 'Plantillas',
                    'route' => 'manager.helpdesk-campaigns.templates',
                    'icon' => 'fas fa-file-lines',
                    'permission' => 'helpdesk.campaigns.manage',
                ],
            ],
        ]);
    }

    public function register(): void
    {
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
        $this->publishes([
            module_path($this->moduleName, 'config/config.php') => config_path($this->moduleNameLower.'.php'),
        ], 'config');

        $this->mergeConfigFrom(
            module_path($this->moduleName, 'config/config.php'),
            $this->moduleNameLower
        );
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
        $managersPath = module_path($this->moduleName, 'routes/managers.php');

        if (file_exists($managersPath)) {
            Route::middleware(['web', 'auth', 'role:super-admin|super-settings'])
                ->prefix('panel/helpdesk')
                ->group($managersPath);
        }
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
