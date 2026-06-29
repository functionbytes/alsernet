<?php

namespace Modules\Remarketing\Tests\Feature;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Modules\Remarketing\Connectors\ConnectorRegistry;
use Modules\Remarketing\Connectors\Shopify\ShopifyConnector;
use Modules\Remarketing\Connectors\WooCommerce\WooCommerceConnector;
use Modules\Remarketing\Contracts\EcommerceConnector;
use Modules\Remarketing\DTOs\EventDTO;
use Modules\Remarketing\Providers\RemarketingServiceProvider;
use Modules\Remarketing\Services\AutomationService;
use Modules\Remarketing\Services\CampaignService;
use Modules\Remarketing\Services\ConsentService;
use Modules\Remarketing\Services\DeliverabilityCheckerService;
use Modules\Remarketing\Services\ProfileService;
use Modules\Remarketing\Services\SegmentService;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Smoke tests del módulo Remarketing.
 *
 * Estos tests asumen que las migraciones del módulo ya se aplicaron
 * (no usan RefreshDatabase para evitar conflictos con migraciones de
 * otros módulos que no son idempotentes).
 */
class RemarketingModuleTest extends TestCase
{
    public function test_remarketing_tables_exist(): void
    {
        if (! Schema::hasTable('remarketing_stores')) {
            $this->markTestSkipped('Migraciones no aplicadas en la BD de testing.');
        }

        $tables = [
            'remarketing_stores',
            'remarketing_customers',
            'remarketing_products',
            'remarketing_orders',
            'remarketing_order_items',
            'remarketing_carts',
            'remarketing_events',
            'remarketing_segments',
            'remarketing_templates',
            'remarketing_automations',
            'remarketing_automation_steps',
            'remarketing_automation_runs',
            'remarketing_campaigns',
            'remarketing_messages',
            'remarketing_suppressions',
            'remarketing_consent_events',
        ];

        foreach ($tables as $table) {
            $this->assertTrue(Schema::hasTable($table), "Falta la tabla {$table}");
        }
    }

    public function test_permissions_exist(): void
    {
        if (Permission::where('name', 'like', 'remarketing.%')->count() === 0) {
            $this->markTestSkipped('Seeder de permisos no aplicado en la BD de testing.');
        }

        $this->assertGreaterThanOrEqual(
            34,
            Permission::where('name', 'like', 'remarketing.%')->count()
        );
    }

    public function test_panel_routes_registered(): void
    {
        $names = [
            'remarketing.dashboard',
            'remarketing.stores.index',
            'remarketing.customers.index',
            'remarketing.segments.index',
            'remarketing.campaigns.index',
            'remarketing.automations.index',
            'remarketing.templates.index',
            'remarketing.reports.index',
            'settings.remarketing.general',
            'settings.remarketing.consent',
            'settings.remarketing.suppressions',
        ];

        foreach ($names as $name) {
            $this->assertTrue(Route::has($name), "Ruta {$name} no registrada.");
        }
    }

    public function test_api_routes_registered(): void
    {
        $names = [
            'api.remarketing.stores.index',
            'api.remarketing.customers.index',
            'api.remarketing.segments.index',
            'api.remarketing.campaigns.index',
            'api.remarketing.automations.index',
            'api.remarketing.templates.index',
            'api.remarketing.suppressions.index',
            'api.remarketing.dsr.export',
            'api.remarketing.dsr.delete',
        ];

        foreach ($names as $name) {
            $this->assertTrue(Route::has($name), "Ruta API {$name} no registrada.");
        }
    }

    public function test_public_routes_registered(): void
    {
        $names = [
            'remarketing.public.track',
            'remarketing.public.unsubscribe',
            'remarketing.public.open',
            'remarketing.public.click',
            'remarketing.public.webhook.shopify',
            'remarketing.public.webhook.woocommerce',
            'remarketing.public.webhook.emailEvents',
        ];

        foreach ($names as $name) {
            $this->assertTrue(Route::has($name), "Ruta pública {$name} no registrada.");
        }
    }

    public function test_artisan_commands_registered(): void
    {
        $kernel = $this->app->make(Kernel::class);
        $commands = array_keys($kernel->all());

        $this->assertContains('remarketing:process-automations', $commands);
        $this->assertContains('remarketing:calculate-rfm', $commands);
        $this->assertContains('remarketing:reconcile-catalog', $commands);
        $this->assertContains('remarketing:mark-abandoned-carts', $commands);
    }

    public function test_pixel_javascript_published(): void
    {
        $this->assertFileExists(public_path('remarketing/pixel.js'));
        $contents = file_get_contents(public_path('remarketing/pixel.js'));
        $this->assertStringContainsString('_rmk_vid', $contents);
        $this->assertStringContainsString('/r/track', $contents);
    }

    public function test_core_classes_load(): void
    {
        $classes = [
            RemarketingServiceProvider::class,
            EcommerceConnector::class,
            EventDTO::class,
            ConnectorRegistry::class,
            ShopifyConnector::class,
            WooCommerceConnector::class,
            ProfileService::class,
            ConsentService::class,
            SegmentService::class,
            CampaignService::class,
            AutomationService::class,
            DeliverabilityCheckerService::class,
        ];

        foreach ($classes as $class) {
            $this->assertTrue(
                class_exists($class) || interface_exists($class),
                "Clase {$class} no carga."
            );
        }
    }
}
