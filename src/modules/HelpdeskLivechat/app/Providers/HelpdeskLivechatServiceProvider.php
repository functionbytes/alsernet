<?php

namespace Modules\HelpdeskLivechat\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Helpdesk\Events\ConversationClosed;
use Modules\Helpdesk\Events\ConversationCreated;
use Modules\Helpdesk\Events\ConversationMessageCreated;
use Modules\HelpdeskLivechat\Console\Commands\CheckIntegrationHealthCommand;
use Modules\HelpdeskLivechat\Console\Commands\ProcessAutoActionsCommand;
use Modules\HelpdeskLivechat\Http\Middleware\ValidateTrustedOrigin;
use Modules\HelpdeskLivechat\Http\Middleware\VerifyWidgetHmac;
use Modules\HelpdeskLivechat\Jobs\PruneLivestreamEventsJob;
use Modules\HelpdeskLivechat\Listeners\EngagementBridgeListener;
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
        $this->registerTranslations();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'database/migrations'));
        $this->registerRoutes();
        $this->registerChannels();
        $this->registerMenus();
        $this->registerEngagementBridge();
        $this->registerEngagementBridgeListeners();
        $this->commands([CheckIntegrationHealthCommand::class, ProcessAutoActionsCommand::class]);

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command('helpdesk-livechat:check-health')
                ->dailyAt('06:00')
                ->onOneServer();

            $schedule->job(new PruneLivestreamEventsJob)
                ->dailyAt('03:30')
                ->onOneServer();

            // Apply per-channel auto-transfer / auto-inactive / auto-close to
            // idle widget conversations. Frequent, but skips when nothing is
            // configured and never overlaps a previous run.
            $schedule->command('helpdesk-livechat:process-auto-actions')
                ->everyFiveMinutes()
                ->withoutOverlapping()
                ->onOneServer();
        });
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
    protected function registerEngagementBridgeListeners(): void
    {
        Event::listen(ConversationCreated::class, [EngagementBridgeListener::class, 'handleConversationCreated']);
        Event::listen(ConversationMessageCreated::class, [EngagementBridgeListener::class, 'handleMessageCreated']);
        Event::listen(ConversationClosed::class, [EngagementBridgeListener::class, 'handleConversationClosed']);
    }

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
        // La configuración del chat web (livechat) se accede desde
        // Settings > Helpdesk > Bandejas > canal Web — no necesita entrada directa en sidebar.
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
        $this->loadApiRoutes();
        $this->loadSettingsRoutes();
    }

    protected function loadApiRoutes(): void
    {
        $path = module_path($this->moduleName, 'routes/api.php');

        if (! file_exists($path)) {
            return;
        }

        Route::middleware(['api', 'throttle:60,1'])
            ->prefix('api/v1/helpdesk-livechat')
            ->name('helpdesklivechat.')
            ->group($path);
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

        Route::middleware(['api', ValidateTrustedOrigin::class, VerifyWidgetHmac::class])
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
