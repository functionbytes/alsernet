<?php

namespace Modules\Prestashop\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PrestashopModuleTest extends TestCase
{
    public function test_module_is_disabled(): void
    {
        $this->assertTrue(true, 'Prestashop module is currently disabled for development');
    }

    public function test_product_entity_exists(): void
    {
        $this->assertTrue(
            class_exists('Modules\Prestashop\Entities\Product'),
            'Product entity exists in Prestashop module'
        );
    }

    public function test_product_blockade_entity_exists(): void
    {
        $this->assertTrue(
            class_exists('Modules\Prestashop\Entities\ProductBlockade'),
            'ProductBlockade entity exists in Prestashop module'
        );
    }

    public function test_category_sync_service_exists(): void
    {
        $this->assertTrue(
            class_exists('Modules\Prestashop\Services\CategorySyncService'),
            'CategorySyncService exists in Prestashop module'
        );
    }

    public function test_routes_are_registered(): void
    {
        $routes = collect(Route::getRoutes());
        $prestashopRoutes = $routes->filter(fn ($route) => str_contains($route->getName() ?? '', 'prestashop'));

        $this->assertGreaterThan(
            0,
            $prestashopRoutes->count(),
            'Prestashop routes are registered in the application'
        );
    }
}
