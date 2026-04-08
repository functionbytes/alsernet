<?php

namespace Modules\Newsletter\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Newsletter\Services\MailjetService;
use Modules\Newsletter\Services\SubscriberService;
use Modules\Theme\Services\NavService;

class NewsletterServiceProvider extends ServiceProvider
{
    protected string $name = 'Newsletter';

    protected string $nameLower = 'newsletter';

    public function boot(): void
    {
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
        $this->loadViewsFrom(module_path($this->name, 'resources/views'), $this->nameLower);
        $this->mergeConfigFrom(module_path($this->name, 'config/config.php'), $this->nameLower);

        $this->registerRoutes();
        $this->registerMenus();
    }

    public function register(): void
    {
        $this->app->singleton(MailjetService::class);
        $this->app->singleton(SubscriberService::class);
    }

    protected function registerRoutes(): void
    {
        $modulePath = dirname(__DIR__, 2);

        Route::middleware(['web', 'auth'])
            ->prefix('panel/newsletter')
            ->name('newsletter.')
            ->group("{$modulePath}/routes/web.php");

        Route::middleware(['web', 'auth'])
            ->prefix('panel/settings/newsletter')
            ->name('settings.newsletter.')
            ->group("{$modulePath}/routes/settings.php");

        Route::middleware(['web'])
            ->group("{$modulePath}/routes/public.php");
    }

    protected function registerMenus(): void
    {
        NavService::addItemsToSection('settings', 'Marketing', [
            [
                'label' => 'Configuracion del newsletter',
                'route' => 'settings.newsletter.index',
                'permission' => 'Newsletter.settings.manage',
            ],
        ]);
    }
}
