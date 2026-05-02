<?php

namespace Modules\HelpdeskLivechat\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Theme\Services\NavService;
use Nwidart\Modules\Facades\Module;

class HelpdeskLivechatServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'HelpdeskLivechat';

    protected string $moduleNameLower = 'helpdesklivechat';

    public function boot(): void
    {
        if (Module::find($this->moduleName)?->isDisabled()) {
            return;
        }

        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'database/migrations'));
        $this->registerRoutes();
        $this->registerChannels();
        $this->registerMenus();
        $this->registerEngagementBridge();
    }

    public function register(): void
    {
        //
    }

    /**
     * If Engagement is active, inject SDK URL + bridge JS into the widget host
     * page. The bridge listens for `trigger:fired` events with action='open_chat'
     * and triggers the widget open. If Engagement is disabled, widget runs
     * standalone with no tracking.
     */
    protected function registerEngagementBridge(): void
    {
        $engagementActive = Module::find('Engagement')?->isEnabled() ?? false;

        view()->share('engagement_active', $engagementActive);
        view()->share(
            'engagement_sdk_url',
            $engagementActive ? url('/build-engagement/sdk.js') : null,
        );
    }

    protected function registerMenus(): void
    {
        if (! class_exists(NavService::class)) {
            return;
        }

        NavService::registerSidebar('settings', [
            'title' => 'Helpdesk',
            'items' => [
                [
                    'label' => 'Chat en vivo',
                    'route' => 'settings.helpdesk-livechat.index',
                    'permission' => 'helpdesk.livechat.settings.view',
                ],
            ],
        ]);

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
        $sourcePath = module_path($this->moduleName, 'resources/views');

        if (! is_dir($sourcePath)) {
            return;
        }

        $publishPath = resource_path('views/modules/'.$this->moduleNameLower);
        $this->publishes([$sourcePath => $publishPath], ['views', $this->moduleNameLower.'-module-views']);

        $publishedPaths = array_values(array_filter(
            array_map(
                fn ($p) => is_dir($p.'/modules/'.$this->moduleNameLower) ? $p.'/modules/'.$this->moduleNameLower : null,
                config('view.paths'),
            ),
        ));

        $this->loadViewsFrom([...$publishedPaths, $sourcePath], $this->moduleNameLower);
    }

    protected function registerRoutes(): void
    {
        $this->loadWebWidgetRoutes();
        $this->loadWidgetApiRoutes();
        $this->loadSettingsRoutes();
    }

    protected function registerChannels(): void
    {
        $path = module_path($this->moduleName, 'routes/channels.php');

        if (file_exists($path)) {
            require $path;
        }
    }

    protected function loadWebWidgetRoutes(): void
    {
        $path = module_path($this->moduleName, 'routes/web-widget.php');

        if (! file_exists($path)) {
            return;
        }

        Route::middleware('web')->group($path);
    }

    protected function loadWidgetApiRoutes(): void
    {
        $path = module_path($this->moduleName, 'routes/widget.php');

        if (! file_exists($path)) {
            return;
        }

        Route::middleware(['api', 'throttle:120,1'])
            ->prefix('hd/api')
            ->name('helpdesk-livechat.widget.')
            ->group($path);
    }

    protected function loadSettingsRoutes(): void
    {
        $path = module_path($this->moduleName, 'routes/settings.php');

        if (! file_exists($path)) {
            return;
        }

        Route::middleware(['web', 'auth'])
            ->prefix('panel/settings/helpdesk')
            ->name('settings.helpdesk-livechat.')
            ->group($path);
    }

    public function provides(): array
    {
        return [];
    }
}
