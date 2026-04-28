<?php

namespace Modules\CampaignSendingServers\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\CampaignSendingServers\Console\Commands\RunHandlersCommand;
use Modules\CampaignSendingServers\Console\Commands\VerifyDkimCommand;
use Modules\CampaignSendingServers\Console\Commands\VerifySendersCommand;
use Modules\CampaignSendingServers\Console\Commands\VerifyTrackingDomainsCommand;
use Modules\Theme\Services\NavService;
use Nwidart\Modules\Facades\Module;

class CampaignSendingServersServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'CampaignSendingServers';

    protected string $moduleNameLower = 'campaignsendingservers';

    public function register(): void
    {
        $this->mergeConfigFrom(
            module_path($this->moduleName, 'config/campaign_sending_servers.php'),
            'campaign_sending_servers'
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

    protected function registerCommands(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            VerifyTrackingDomainsCommand::class,
            VerifySendersCommand::class,
            VerifyDkimCommand::class,
            RunHandlersCommand::class,
        ]);
    }

    protected function registerConfig(): void
    {
        $this->publishes([
            module_path($this->moduleName, 'config/campaign_sending_servers.php') => config_path('campaign_sending_servers.php'),
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
                ->prefix('panel/sending-servers')
                ->name('manager.sending-servers.')
                ->group($managersPath);
        }

        $apiPath = module_path($this->moduleName, 'routes/api.php');
        if (file_exists($apiPath)) {
            // Throttle: 60 req/min reads. Endpoints "test" tienen su propio throttle más agresivo en routes/api.php.
            Route::middleware(['api', 'auth:sanctum', 'throttle:60,1'])
                ->prefix('api/sending-servers')
                ->name('api.sending-servers.')
                ->group($apiPath);
        }
    }

    protected function registerMenus(): void
    {
        if (! class_exists(NavService::class)) {
            return;
        }

        NavService::registerSidebar('campaign_sending_servers', [
            'title' => 'Servidores de envío',
            'permission' => 'campaign_sending_servers.view.all',
            'items' => [
                [
                    'label' => 'Servidores',
                    'route' => 'manager.sending-servers.index',
                    'icon' => 'fas fa-server',
                    'permission' => 'campaign_sending_servers.view.all',
                ],
                [
                    'label' => 'Dominios de envío',
                    'route' => 'manager.sending-servers.domains.index',
                    'icon' => 'fas fa-globe',
                    'permission' => 'campaign_sending_servers.domains.view',
                ],
                [
                    'label' => 'Dominios de tracking',
                    'route' => 'manager.sending-servers.tracking-domains.index',
                    'icon' => 'fas fa-link',
                    'permission' => 'campaign_sending_servers.tracking_domains.view',
                ],
                [
                    'label' => 'Lista negra',
                    'route' => 'manager.sending-servers.blacklist.index',
                    'icon' => 'fas fa-ban',
                    'permission' => 'campaign_sending_servers.blacklist.view',
                ],
            ],
        ]);
    }

    protected function registerSchedules(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);

            $schedule->command('campaign-sending-servers:verify-tracking-domains')
                ->name('campaign_sending_servers:verify_tracking_domains')
                ->hourly();

            $schedule->command('campaign-sending-servers:verify-senders')
                ->name('campaign_sending_servers:verify_senders')
                ->hourly();

            $schedule->command('campaign-sending-servers:run-handlers')
                ->name('campaign_sending_servers:run_handlers')
                ->everyFiveMinutes();
        });
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
