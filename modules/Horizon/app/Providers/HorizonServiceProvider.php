<?php

namespace Modules\Horizon\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Theme\Services\NavService;

class HorizonServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);

        // Merge module config
        $this->mergeConfigFrom(
            __DIR__.'/../../config/horizon.php',
            'horizon'
        );
    }

    public function boot(): void
    {
        $this->loadViewsFrom(
            module_path('Horizon', 'resources/views'),
            'horizon'
        );
        $this->registerGates();
        $this->registerMenus();
    }

    private function registerGates(): void
    {
        Gate::define('view-horizon', function ($user = null) {
            if (! $user) {
                return false;
            }

            return $user->hasAnyRole(['super-admin', 'manager']);
        });

        Gate::define('manage-horizon-backups', function ($user = null) {
            if (! $user) {
                return false;
            }

            return $user->hasRole('super-admin');
        });
    }

    private function registerMenus(): void
    {

        // Register sidebar group with submenu items
        NavService::registerSidebar('settings', [
            'title' => 'Horizon',
            'items' => [
                [
                    'label' => 'Dashboard',
                    'route' => 'settings.horizon.index',
                ],
                [
                    'label' => 'Trabajos activos',
                    'route' => 'settings.horizon.jobs',
                ],
                [
                    'label' => 'Trabajos fallidos',
                    'route' => 'settings.horizon.failed',
                ],
                [
                    'label' => 'Métricas',
                    'route' => 'settings.horizon.metrics',
                ],
            ],
        ]);
    }
}
