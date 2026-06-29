<?php

namespace Modules\HelpdeskContacts\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Theme\Services\NavService;
use Nwidart\Modules\Facades\Module;

class HelpdeskContactsServiceProvider extends ServiceProvider
{
    protected string $name = 'HelpdeskContacts';

    protected string $nameLower = 'contacts';

    public function register(): void
    {
        $this->mergeConfigFrom(
            module_path($this->name, 'config/config.php'),
            $this->nameLower
        );
    }

    public function boot(): void
    {
        if (Module::find($this->name)?->isDisabled()) {
            return;
        }

        $this->loadViewsFrom(module_path($this->name, 'resources/views'), 'contacts');
        $this->registerRoutes();
        $this->registerNav();
    }

    protected function registerRoutes(): void
    {
        $web = module_path($this->name, 'routes/web.php');

        if (! file_exists($web)) {
            return;
        }

        Route::middleware(['web', 'auth'])
            ->prefix('panel/contacts')
            ->group($web);
    }

    protected function registerNav(): void
    {
        if (! class_exists(NavService::class)) {
            return;
        }

        NavService::registerMiniItem('contacts', [
            'icon' => 'fas fa-address-book',
            'tooltip' => 'Contactos',
            'sidebar_id' => 'contacts',
            'order' => 71,
        ]);

        NavService::registerSidebar('contacts', [
            'title' => 'CRM',
            'items' => [
                [
                    'label' => 'Contactos',
                    'route' => 'contacts.index',
                    'icon' => 'fas fa-address-book',
                    'permission' => 'contacts.view',
                ],
            ],
        ]);
    }
}
