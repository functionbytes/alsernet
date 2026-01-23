<?php

namespace Modules\Role\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Theme\Services\NavService;

class RoleServiceProvider extends ServiceProvider
{
    protected string $name = 'Role';

    public function register()
    {
        // Merge module configs
        $this->mergeConfigFrom(
            __DIR__.'/../../config/permission.php',
            'permission'
        );

        $this->mergeConfigFrom(
            __DIR__.'/../../config/permissions.php',
            'permissions'
        );

        $this->mergeConfigFrom(
            __DIR__.'/../../config/validation-permissions.php',
            'validation-permissions'
        );

        // Register PermissionBladeServiceProvider
        $this->app->register(PermissionBladeServiceProvider::class);
    }

    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'role');

        $this->publishes([
            __DIR__.'/../../config/role.php' => config_path('role.php'),
        ], 'role-config');

        // Register navigation menus
        $this->registerMenus();

        // Register routes after all providers have booted
        $this->booted(function () {
            $this->registerRoutes();
        });
    }

    /**
     * Register routes for the Role module
     */
    protected function registerRoutes(): void
    {
        $modulePath = dirname(__DIR__, 2);

        // Manager settings routes (GET views + POST/PUT/DELETE API)
        Route::middleware(['web', 'auth', 'role:super-admin'])
            ->prefix('settings')
            ->name('settings.')
            ->group(function () use ($modulePath) {
                // Load view routes (GET)
                require $modulePath.'/routes/web.php';

                // Load API routes (POST, PUT, DELETE) if exists
                if (file_exists($modulePath.'/routes/api.php')) {
                    require $modulePath.'/routes/api.php';
                }
            });
    }

    /**
     * Register navigation menus for the Role module
     */
    protected function registerMenus(): void
    {
        // Mini-nav item for Roles & Permissions
        NavService::registerMiniItem('roles', [
            'icon' => 'fa-duotone fa-shield',
            'tooltip' => 'Roles y Permisos',
            'sidebar_id' => 'roles',
            'order' => 40,
        ]);

        // Sidebar with menu items
        NavService::registerSidebar('roles', [
            'title' => 'Roles y Permisos',
            'items' => [
                ['label' => 'Roles', 'route' => 'settings.roles.index'],
                ['label' => 'Permisos', 'route' => 'settings.permissions.index'],
            ],
        ]);
    }
}
