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
            ->prefix('panel/helpdesk/contacts')
            ->group($web);

        // Redirect 301 catch-all: conserva enlaces/bookmarks al prefix
        // anterior (panel/contacts) tras moverlo bajo panel/helpdesk/*.
        Route::middleware(['web'])
            ->get('panel/contacts/{path?}', fn (string $path = '') => redirect('panel/helpdesk/contacts/'.$path, 301))
            ->where('path', '.*');
    }

    protected function registerNav(): void
    {
        if (! class_exists(NavService::class)) {
            return;
        }

        if (! helpdesk_contacts_enabled()) {
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

        // Se fusiona en la sección "Bandeja" ya registrada por
        // HelpdeskServiceProvider (mismo sidebar_id 'helpdesk' + mismo
        // título) — acceso directo a Contactos sin cambiar de icono en
        // el rail, ademas del propio icono/sidebar dedicado de arriba.
        NavService::registerSidebar('helpdesk', [
            'title' => 'Bandeja',
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
