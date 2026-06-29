<?php

namespace Modules\Role\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Role\Events\RoleCreated;
use Modules\Role\Events\RoleDeleted;
use Modules\Role\Events\RoleUpdated;
use Modules\Role\Events\UserRoleChanged;
use Modules\Role\Listeners\ClearRolePermissionCacheListener;
use Modules\Role\Listeners\UserRoleChangedListener;
use Modules\Theme\Services\NavService;

class RoleServiceProvider extends ServiceProvider
{
    protected string $name = 'Role';

    public function register()
    {
        // Merge module configs
        $this->mergeConfigFrom(
            __DIR__.'/../../config/role.php',
            'role'
        );

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
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'role');

        $this->publishes([
            __DIR__.'/../../config/role.php' => config_path('role.php'),
        ], 'role-config');

        // Register navigation menus
        $this->registerMenus();

        // Register event listeners
        $this->registerListeners();

        // Register routes after all providers have booted
        $this->booted(function () {
            $this->registerRoutes();
        });
    }

    protected function registerListeners(): void
    {
        Event::listen(RoleCreated::class, ClearRolePermissionCacheListener::class);
        Event::listen(RoleUpdated::class, ClearRolePermissionCacheListener::class);
        Event::listen(RoleDeleted::class, ClearRolePermissionCacheListener::class);
        Event::listen(UserRoleChanged::class, UserRoleChangedListener::class);
    }

    /**
     * Register routes for the Role module
     */
    protected function registerRoutes(): void
    {
        $modulePath = dirname(__DIR__, 2);

        // Manager settings routes (GET views + POST/PUT/DELETE API)
        Route::middleware(['web', 'auth', 'settings'])
            ->prefix('panel/settings')
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

        // Sidebar with menu items
        NavService::registerSidebar('settings', [
            'title' => 'Roles y Permisos',
            'items' => [
                ['label' => 'Roles', 'route' => 'settings.roles.index'],
                ['label' => 'Permisos', 'route' => 'settings.permissions.index'],
                ['label' => 'Permisos directos', 'route' => 'settings.user-permissions.index'],
            ],
        ]);
    }
}
