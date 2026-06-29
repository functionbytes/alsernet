<?php

namespace Modules\Engagement\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Engagement\Console\Commands\AnonymizeOldIpsCommand;
use Modules\Engagement\Console\Commands\ArchiveEventsCommand;
use Modules\Engagement\Console\Commands\CheckIntegrationHealthCommand;
use Modules\Engagement\Console\Commands\SendScheduledCampaignsCommand;
use Modules\Engagement\Http\Middleware\EnsureWebsiteToken;
use Modules\Engagement\Jobs\ArchiveOldEventsJob;
use Modules\Engagement\Listeners\AttributeRevenueOnPsOrderListener;
use Modules\Engagement\Listeners\BoostScoreOnPsOrderListener;
use Modules\Engagement\Listeners\BroadcastOrderTrackingListener;
use Modules\HelpdeskPrestashop\Events\PsOrderCreated;
use Modules\HelpdeskPrestashop\Events\PsOrderStatusChanged;
use Modules\Theme\Services\NavService;
use Nwidart\Modules\Facades\Module;

class EngagementServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Engagement';

    protected string $moduleNameLower = 'engagement';

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
        $this->registerSchedule();
        $this->registerCommands();
        $this->registerRateLimiters();
        $this->registerEventListeners();
    }

    protected function registerEventListeners(): void
    {
        $events = [
            PsOrderCreated::class => [
                AttributeRevenueOnPsOrderListener::class,
                BoostScoreOnPsOrderListener::class,
            ],
            PsOrderStatusChanged::class => [
                BroadcastOrderTrackingListener::class,
            ],
        ];

        foreach ($events as $event => $listeners) {
            if (! class_exists($event)) {
                continue;
            }
            foreach ($listeners as $listener) {
                if (class_exists($listener)) {
                    Event::listen($event, $listener);
                }
            }
        }
    }

    protected function registerRateLimiters(): void
    {
        RateLimiter::for('engagement-sdk', function (Request $request): Limit {
            $token = $request->header('X-Website-Token') ?? $request->input('website_token');

            return Limit::perMinute(60)->by($request->ip().'|'.($token ?? 'no-token'));
        });
    }

    protected function registerConfig(): void
    {
        $this->mergeConfigFrom(
            module_path($this->moduleName, 'config/config.php'),
            $this->moduleNameLower
        );

        $this->mergeConfigFrom(
            module_path($this->moduleName, 'config/services.php'),
            $this->moduleNameLower.'.services'
        );
    }

    public function register(): void
    {
        $this->app['router']->aliasMiddleware('engagement.token', EnsureWebsiteToken::class);
    }

    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                AnonymizeOldIpsCommand::class,
                ArchiveEventsCommand::class,
                CheckIntegrationHealthCommand::class,
                SendScheduledCampaignsCommand::class,
            ]);
        }
    }

    protected function registerSchedule(): void
    {
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command('engagement:check-health')
                ->hourly()
                ->withoutOverlapping();

            $schedule->job(new ArchiveOldEventsJob)
                ->weekly()
                ->sundays()
                ->at('02:00')
                ->withoutOverlapping();

            $schedule->command('engagement:send-scheduled-campaigns')
                ->everyFiveMinutes()
                ->withoutOverlapping();

            $schedule->command('engagement:anonymize-old-ips --days=30')
                ->dailyAt('03:30')
                ->withoutOverlapping();
        });
    }

    protected function registerMenus(): void
    {
        if (! class_exists(NavService::class)) {
            return;
        }

        NavService::registerMiniItem('engagement', [
            'icon' => 'fa-duotone fa-thin fa-bolt',
            'tooltip' => 'Engagement',
            'sidebar_id' => 'main',
            'order' => 90,
        ]);

        NavService::registerSidebar('settings', [
            'title' => 'Engagement',
            'items' => [
                ['label' => 'Inicio',                'route' => 'settings.engagement.index',                 'permission' => 'engagement.triggers.view'],
                ['label' => 'Reglas de activación',  'route' => 'settings.engagement.triggers.page',         'permission' => 'engagement.triggers.view'],
                ['label' => 'Segmentos',             'route' => 'settings.engagement.segments.page',         'permission' => 'engagement.segments.view'],
                ['label' => 'AB Tests',              'route' => 'settings.engagement.ab-tests.page',         'permission' => 'engagement.ab_tests.view'],
                ['label' => 'Personalización DOM',   'route' => 'settings.engagement.personalizations.page', 'permission' => 'engagement.personalizations.view'],
                ['label' => 'Canales web',           'route' => 'settings.engagement.web-channels.page',     'permission' => 'engagement.web_channels.view'],
                ['label' => 'Integraciones',         'route' => 'settings.engagement.platforms.page',        'permission' => 'engagement.platforms.view'],
                ['label' => 'Automation',            'route' => 'settings.engagement.automation.page',       'permission' => 'engagement.automation.view'],
                ['label' => 'Objetivos',             'route' => 'settings.engagement.goals.page',            'permission' => 'engagement.goals.view'],
                ['label' => 'Webhook logs',          'route' => 'settings.engagement.webhook-logs.page',     'permission' => 'engagement.platforms.view'],
                ['label' => 'Campañas email',        'route' => 'settings.engagement.email-campaigns.page',  'permission' => 'engagement.email_campaigns.view'],
                ['label' => 'Audit log',             'route' => 'settings.engagement.audit-logs.page',       'permission' => 'engagement.manage'],
            ],
        ]);

        NavService::registerSidebar('main', [
            'title' => 'Engagement',
            'items' => [
                ['label' => 'Visitantes en vivo', 'route' => 'manager.engagement.live-visitors.index', 'permission' => 'engagement.events.view'],
                ['label' => 'Analytics',          'route' => 'manager.engagement.analytics.page',     'permission' => 'engagement.events.view'],
            ],
        ]);
    }

    protected function registerViews(): void
    {
        $sourcePath = module_path($this->moduleName, 'resources/views');
        if (is_dir($sourcePath)) {
            $this->loadViewsFrom([$sourcePath], $this->moduleNameLower);
        }
    }

    protected function registerRoutes(): void
    {
        $this->loadSdkRoutes();
        $this->loadSettingsRoutes();
        $this->loadManagerRoutes();
    }

    protected function loadSdkRoutes(): void
    {
        $path = module_path($this->moduleName, 'routes/sdk.php');
        if (! file_exists($path)) {
            return;
        }

        Route::middleware(['api'])
            ->prefix('eng/api')
            ->name('engagement.')
            ->group($path);

        // Compatibilidad: alias a las URLs antiguas /hd/api/sdk/* durante 6 meses
        Route::middleware(['api'])
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
            ->prefix('panel/settings/engagement')
            ->name('settings.engagement.')
            ->group($path);
    }

    protected function loadManagerRoutes(): void
    {
        $path = module_path($this->moduleName, 'routes/managers.php');
        if (! file_exists($path)) {
            return;
        }

        Route::middleware(['web', 'auth'])
            ->prefix('panel/engagement')
            ->name('manager.engagement.')
            ->group($path);
    }

    protected function registerChannels(): void
    {
        $path = module_path($this->moduleName, 'routes/channels.php');

        if (file_exists($path)) {
            require $path;
        }
    }
}
