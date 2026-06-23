<?php

namespace Modules\HelpdeskAnalytics\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Theme\Services\NavService;
use Nwidart\Modules\Facades\Module;

class HelpdeskAnalyticsServiceProvider extends ServiceProvider
{
    protected string $name = 'HelpdeskAnalytics';

    protected string $nameLower = 'helpdeskanalytics';

    public function register(): void
    {
        $this->mergeConfigFrom(
            module_path($this->name, 'config/config.php'),
            $this->nameLower
        );
    }

    public function boot(): void
    {
        if (Module::find($this->name)?->isDisabled()) {
            return;
        }

        $this->loadTranslationsFrom(module_path($this->name, 'lang'), $this->nameLower);
        $this->loadViewsFrom(module_path($this->name, 'resources/views'), $this->nameLower);

        $this->registerRoutes();
        $this->registerNav();
    }

    protected function registerRoutes(): void
    {
        $web = module_path($this->name, 'routes/web.php');

        if (! file_exists($web)) {
            return;
        }

        Route::middleware(['web', 'auth'])
            ->prefix('panel/helpdeskanalytics')
            ->group($web);
    }

    protected function registerNav(): void
    {
        if (! class_exists(NavService::class)) {
            return;
        }

        NavService::registerMiniItem('helpdeskanalytics', [
            'icon' => 'fas fa-chart-line',
            'tooltip' => 'Analytics',
            'sidebar_id' => 'helpdeskanalytics',
            'order' => 72,
        ]);

        NavService::registerSidebar('helpdeskanalytics', [
            'title' => 'Analytics',
            'items' => [
                [
                    'label' => 'Dashboard',
                    'route' => 'helpdeskanalytics.index',
                    'icon' => 'fas fa-chart-line',
                    'permission' => 'helpdeskanalytics.view',
                ],
            ],
        ]);
    }
}
