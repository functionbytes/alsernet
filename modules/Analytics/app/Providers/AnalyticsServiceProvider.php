<?php

namespace Modules\Analytics\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Modules\Analytics\Abstracts\AnalyticsAbstract;
use Modules\Analytics\Analytics;
use Modules\Analytics\Console\Commands\DispatchAnalyticsSchedulesCommand;
use Modules\Analytics\Console\Commands\GenerateAnalyticsReportCommand;
use Modules\Analytics\Events\ReportGenerated;
use Modules\Analytics\Exceptions\InvalidConfiguration;
use Modules\Analytics\Facades\Analytics as AnalyticsFacade;
use Modules\Analytics\Listeners\LogReportGenerated;
use Modules\Theme\Services\NavService;
use Nwidart\Modules\Facades\Module;

class AnalyticsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AnalyticsAbstract::class, function () {
            if (! ($credentials = setting('analytics_service_account_credentials'))) {
                throw InvalidConfiguration::credentialsIsNotValid();
            }

            if (! ($propertyId = setting('analytics_property_id')) || ! is_numeric($propertyId)) {
                throw InvalidConfiguration::invalidPropertyId();
            }

            return new Analytics($propertyId, $credentials);
        });

        AliasLoader::getInstance()->alias('Analytics', AnalyticsFacade::class);
    }

    public function boot(): void
    {
        if (Module::find('Analytics')?->isDisabled()) {
            return;
        }

        $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'analytics');
        $this->loadTranslationsFrom(__DIR__.'/../../resources/lang', 'analytics');
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        $this->publishes([
            __DIR__.'/../../config/analytics.php' => config_path('analytics.php'),
        ], 'analytics-config');

        $this->publishes([
            __DIR__.'/../../resources/views' => resource_path('views/vendor/analytics'),
        ], 'analytics-views');

        $this->registerCommands();
        $this->registerScheduledTasks();
        $this->registerMenus();
        $this->registerEventListeners();
    }

    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateAnalyticsReportCommand::class,
                DispatchAnalyticsSchedulesCommand::class,
            ]);
        }
    }

    protected function registerScheduledTasks(): void
    {
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command('analytics:dispatch-schedules')
                ->everyFifteenMinutes()
                ->name('analytics:dispatch-schedules')
                ->withoutOverlapping()
                ->onOneServer();
        });
    }

    protected function registerEventListeners(): void
    {
        Event::listen(ReportGenerated::class, LogReportGenerated::class);
    }

    protected function registerMenus(): void
    {
        NavService::registerSidebar('settings', [
            'title' => 'Analytics',
            'items' => [
                ['label' => 'Dashboard', 'route' => 'analytics.dashboard'],
                ['label' => 'Configuracion', 'route' => 'settings.analytics.index'],
                ['label' => 'Reportes programados', 'route' => 'settings.analytics.schedules.index'],
                ['label' => 'Notificaciones', 'route' => 'settings.analytics.notifications'],
            ],
        ]);
    }

    public function provides(): array
    {
        return [
            AnalyticsAbstract::class,
        ];
    }
}
