<?php

namespace Modules\User\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Theme\Services\NavService;
use Modules\User\Http\Controllers\UsersController;

class UserServiceProvider extends ServiceProvider
{
    protected string $name = 'User';

    public function register()
    {
        // Register User services
    }

    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'user');

        $this->publishes([
            __DIR__.'/../../config/user.php' => config_path('user.php'),
        ], 'user-config');

        // Register navigation menus
        $this->registerMenus();

        // Register routes after all providers have booted
        $this->booted(function () {
            $this->registerRoutes();
        });
    }

    /**
     * Register routes for the User module
     */
    protected function registerRoutes(): void
    {
        $modulePath = dirname(__DIR__, 2);

        // Public search route FIRST (accessible to authenticated users for role assignment)
        // Must be registered before the greedy {uid} route
        Route::middleware(['web', 'auth'])
            ->prefix('settings/users')
            ->name('settings.users.')
            ->group(function () {
                Route::get('/search', [UsersController::class, 'search'])->name('search');
            });

        // User settings routes (GET views + POST/PUT/DELETE API)
        Route::middleware(['web', 'auth', 'role:super-admin'])
            ->prefix('settings/users')
            ->name('settings.users.')
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
     * Register navigation menus for the User module
     */
    protected function registerMenus(): void
    {
        // Mini-nav item for Users
        NavService::registerMiniItem('users', [
            'icon' => 'fa-duotone fa-thin fa-users-medical',
            'tooltip' => 'Usuarios',
            'sidebar_id' => 'users',
            'order' => 30,
        ]);

        // Sidebar with menu items
        NavService::registerSidebar('users', [
            'title' => 'Usuarios',
            'items' => [
                ['label' => 'Todos los usuarios', 'route' => 'settings.users.index'],
            ],
        ]);
    }
}
