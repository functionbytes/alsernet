<?php

namespace Modules\Subscriber\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Modules\Theme\Services\NavService;

class SubscriberServiceProvider extends ServiceProvider
{
    protected string $name = 'Subscriber';

    protected string $nameLower = 'subscriber';

    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
        $this->registerEventListeners();
        $this->registerMenus();
        $this->registerSchedules();
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }

    protected function registerEventListeners(): void
    {
        Event::listen(
            \Modules\Subscriber\Events\SubscriberCheckatEvent::class,
            \Modules\Subscriber\Listeners\SubscriberCheckatListener::class
        );

        Event::listen(
            \App\Events\MailListSubscription::class,
            \Modules\Subscriber\Listeners\SendListNotificationToOwner::class
        );

        Event::listen(
            \App\Events\MailListSubscription::class,
            \Modules\Subscriber\Listeners\SendListNotificationToSubscriber::class
        );

        // Unsubscription events
        Event::listen(
            \App\Events\MailListUnsubscription::class,
            \Modules\Subscriber\Listeners\SendListNotificationToOwner::class
        );

        Event::listen(
            \App\Events\MailListUnsubscription::class,
            \Modules\Subscriber\Listeners\SendListNotificationToSubscriber::class
        );
    }

    protected function registerTranslations(): void
    {
        $langPath = module_path($this->name, 'lang');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->nameLower);
            $this->loadJsonTranslationsFrom($langPath);
        }
    }

    protected function registerConfig(): void
    {
        $this->publishes([
            module_path($this->name, 'config/config.php') => config_path($this->nameLower.'.php'),
        ], 'config');

        $this->mergeConfigFrom(
            module_path($this->name, 'config/config.php'),
            $this->nameLower
        );
    }

    protected function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->nameLower);
        $sourcePath = module_path($this->name, 'resources/views');

        $this->publishes([
            $sourcePath => $viewPath,
        ], ['views', $this->nameLower.'-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->nameLower);
    }

    protected function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach ($this->app['config']->get('view.paths') as $path) {
            if (is_dir($path.'/modules/'.$this->nameLower)) {
                $paths[] = $path.'/modules/'.$this->nameLower;
            }
        }

        return $paths;
    }

    public function provides(): array
    {
        return [];
    }

    /**
     * Register scheduled tasks for Subscriber module
     */
    protected function registerSchedules(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);

            // Update disposable email list weekly
            $schedule->command('disposable:update')->weekly();

            // Remove inactive customers every minute
            $schedule->command('customer:inactive_delete')->everyMinute();
        });
    }

    /**
     * Registrar menús del módulo Subscriber
     */
    protected function registerMenus(): void
    {
        // Mini-nav item para Subscribers
        NavService::registerMiniItem('subscribers', [
            'icon' => 'fa-duotone fa-envelope',
            'tooltip' => 'Suscriptores',
            'sidebar_id' => 'subscribers',
            'order' => 50,
        ]);

        // Sidebar con los items del módulo
        NavService::registerSidebar('subscribers', [
            'title' => 'Suscriptores',
            'items' => [
                ['label' => 'Suscriptores', 'route' => 'subscribers.index', 'icon' => 'fa-duotone fa-user'],
                ['label' => 'Listas de correo', 'route' => 'subscribers.lists.index', 'icon' => 'fa-duotone fa-list'],
                ['label' => 'Condiciones', 'route' => 'subscribers.conditions.index', 'icon' => 'fa-duotone fa-filter'],
            ],
        ]);
    }
}
