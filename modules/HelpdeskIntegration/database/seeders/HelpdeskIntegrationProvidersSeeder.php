<?php

namespace Modules\HelpdeskIntegration\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\HelpdeskIntegration\Models\IntegrationProvider;

/**
 * Filas nativas del catalogo: reflejan los drivers registrados en
 * HelpdeskIntegrationServiceProvider::registerDrivers(). Se usan
 * updateOrCreate() por platform para ser idempotente entre despliegues.
 */
class HelpdeskIntegrationProvidersSeeder extends Seeder
{
    public function run(): void
    {
        IntegrationProvider::query()->updateOrCreate(
            ['platform' => 'prestashop'],
            [
                'driver' => 'prestashop',
                'label' => 'PrestaShop',
                'description' => 'Tienda online',
                'icon' => 'fas fa-cart-shopping',
                'color' => '#df1d1d',
                'is_active' => true,
                'is_linkable' => true,
                'is_critical' => false,
                // null: CustomerIntegrationService::presentLinkable() cae en
                // PrestashopIntegrationDriver::defaultSearchTypes(), la fuente
                // de verdad de lo que el driver realmente matchea.
                'search_types' => null,
                'sort_order' => 10,
            ],
        );

        IntegrationProvider::query()->updateOrCreate(
            ['platform' => 'erp'],
            [
                'driver' => 'erp',
                'label' => 'Gestión (ERP)',
                'description' => 'Gestión y stock',
                'icon' => 'fas fa-clipboard-list',
                'color' => '#f59e0b',
                'is_active' => true,
                'is_linkable' => true,
                'is_critical' => true,
                // null: CustomerIntegrationService::presentLinkable() cae en
                // ErpIntegrationDriver::defaultSearchTypes(), la fuente de
                // verdad de lo que el driver realmente matchea.
                'search_types' => null,
                'sort_order' => 20,
            ],
        );
    }
}
