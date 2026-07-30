<?php

namespace Modules\HelpdeskEmailLog\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\HelpdeskEmailLog\Console\Commands\PruneEmailLogsCommand;
use Modules\HelpdeskEmailLog\Listeners\LogEmailQueued;
use Modules\HelpdeskEmailLog\Listeners\LogEmailSent;
use Modules\HelpdeskEmailLog\Models\EmailLog;
use Modules\HelpdeskEmailLog\Policies\EmailLogPolicy;
use Modules\Theme\Services\NavService;
use Nwidart\Modules\Facades\Module;

class HelpdeskEmailLogServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'HelpdeskEmailLog';

    protected string $moduleNameLower = 'helpdeskemaillog';

    public function boot(): void
    {
        if (Module::find($this->moduleName)?->isDisabled()) {
            return;
        }

        $this->registerConfig();
        $this->registerTranslations();
        $this->registerViews();
        $this->registerAssets();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'database/migrations'));
        $this->registerRoutes();
        $this->registerListeners();
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerPolicies();
        $this->registerMenus();
    }

    protected function registerPolicies(): void
    {
        Gate::policy(EmailLog::class, EmailLogPolicy::class);
    }

    public function register(): void {}

    protected function registerConfig(): void
    {
        $path = module_path($this->moduleName, 'config/config.php');

        $this->publishes([$path => config_path($this->moduleNameLower.'.php')], 'config');
        $this->mergeConfigFrom($path, $this->moduleNameLower);
    }

    protected function registerAssets(): void
    {
        $this->publishes([
            module_path($this->moduleName, 'public') => public_path('modules/'.$this->moduleNameLower),
        ], $this->moduleNameLower.'-assets');
    }

    protected function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/'.$this->moduleNameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->moduleNameLower);
        } else {
            $this->loadTranslationsFrom(module_path($this->moduleName, 'lang'), $this->moduleNameLower);
        }
    }

    protected function registerViews(): void
    {
        $sourcePath = module_path($this->moduleName, 'resources/views');
        $publishPath = resource_path('views/modules/'.$this->moduleNameLower);

        $this->publishes([$sourcePath => $publishPath], ['views', $this->moduleNameLower.'-module-views']);

        $published = array_values(array_filter(array_map(
            fn ($p) => is_dir($p.'/modules/'.$this->moduleNameLower) ? $p.'/modules/'.$this->moduleNameLower : null,
            config('view.paths', []),
        )));

        $this->loadViewsFrom([...$published, $sourcePath], $this->moduleNameLower);
    }

    protected function registerRoutes(): void
    {
        Route::middleware('web')->group(module_path($this->moduleName, 'routes/web.php'));
    }

    protected function registerListeners(): void
    {
        Event::listen(MessageSending::class, LogEmailQueued::class);
        Event::listen(MessageSent::class, LogEmailSent::class);
    }

    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([PruneEmailLogsCommand::class]);
        }
    }

    protected function registerCommandSchedules(): void
    {
        $this->app->booted(function () {
            $this->app->make(Schedule::class)
                ->command('email-logs:prune')
                ->daily()
                ->at('03:30')
                ->withoutOverlapping()
                ->onOneServer()
                ->runInBackground();
        });
    }

    protected function registerMenus(): void
    {
        if (! class_exists(NavService::class)) {
            return;
        }

        if (! helpdesk_emaillog_enabled()) {
            return;
        }

        NavService::registerSidebar('helpdesk', [
            'title' => 'Email',
            'items' => [
                [
                    'label' => 'Log de emails',
                    'route' => 'helpdeskemaillog.index',
                    'icon' => 'fas fa-envelope-open-text',
                    'permission' => 'helpdeskemaillog.view',
                ],
            ],
        ]);

        NavService::addItemsToSection('settings', 'Helpdesk — Canales', [
            [
                'label' => 'Log de emails',
                'route' => 'settings.helpdeskemaillog.index',
                'permission' => 'helpdeskemaillog.settings.view',
            ],
        ]);
    }
}
