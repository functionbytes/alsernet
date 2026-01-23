<?php

namespace Modules\Campaign\Providers;

use App\Helpers\ModuleStatusHelper;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use Modules\Campaign\Entities\Automation;
use Modules\Campaign\Entities\Campaign;
use Modules\Campaign\Events\CronJobExecuted;

class CampaignServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/campaign.php',
            'campaign'
        );
        $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // ✅ CRITICAL: Only boot module features if Campaign module is enabled
        if (! ModuleStatusHelper::isModuleEnabled('Campaign')) {
            return;
        }

        $this->publishConfig();
        $this->registerViewComposers();
        $this->registerTranslationHooks();
        $this->registerSchedules();
    }

    /**
     * Publish the package configuration file.
     */
    private function publishConfig(): void
    {
        $this->publishes([
            __DIR__.'/../../config/campaign.php' => config_path('campaign.php'),
        ], 'campaign-config');
    }

    /**
     * Register view composers.
     */
    protected function registerViewComposers(): void
    {
        // Register navigation composer for theme layout
        view()->composer(
            'theme.components.nav',
            \Modules\Campaign\Http\ViewComposers\NavigationComposer::class
        );
    }

    /**
     * Register translation hooks from Campaign module
     */
    protected function registerTranslationHooks(): void
    {
        // Load custom translation files from Campaign hooks
        if (class_exists(\Modules\Campaign\Library\Facades\Hook::class)) {
            foreach (\Modules\Campaign\Library\Facades\Hook::execute('add_translation_file') as $source) {
                if (! empty($source['translation_prefix']) && ! empty($source['translation_folder'])) {
                    $this->loadTranslationsFrom($source['translation_folder'], $source['translation_prefix']);
                }
            }
        }
    }

    /**
     * Register scheduled tasks for Campaign module
     */
    protected function registerSchedules(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);

            // CronJob event logging - every minute
            $schedule->call(function () {
                CronJobExecuted::dispatch();
            })->name('cronjob_event:log')->everyMinute();

            // Automation execution - every 5 minutes
            $schedule->call(function () {
                Automation::run();
            })->name('automation:run')->everyFiveMinutes();

            // Check and execute scheduled campaigns - every minute
            $schedule->call(function () {
                Campaign::checkAndExecuteScheduledCampaigns();
            })->name('check_and_execute_scheduled_campaigns')->everyMinute();
        });
    }
}
