<?php

namespace Modules\Supplier\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Modules\Supplier\Http\Controllers\Settings\Suppliers\SupplierProductsController;
use Tests\TestCase;

class ProductRoutesTest extends TestCase
{
    public function test_controller_class_exists(): void
    {
        $this->assertTrue(class_exists(SupplierProductsController::class));
    }

    public function test_settings_products_index_route_exists(): void
    {
        $this->assertTrue(Route::has('settings.suppliers.products.index'));
    }

    public function test_settings_products_show_route_exists(): void
    {
        $this->assertTrue(Route::has('settings.suppliers.products.show'));
    }

    public function test_settings_products_edit_route_exists(): void
    {
        $this->assertTrue(Route::has('settings.suppliers.products.edit'));
    }

    public function test_settings_products_update_route_exists(): void
    {
        $this->assertTrue(Route::has('settings.suppliers.products.update'));
    }

    public function test_settings_products_bulk_action_route_exists(): void
    {
        $this->assertTrue(Route::has('settings.suppliers.products.bulk-action'));
    }

    public function test_settings_products_sync_route_exists(): void
    {
        $this->assertTrue(Route::has('settings.suppliers.products.sync'));
    }

    public function test_product_routes_use_correct_methods(): void
    {
        $this->assertSame('GET', Route::getRoutes()->getByName('settings.suppliers.products.index')->methods()[0]);
        $this->assertSame('GET', Route::getRoutes()->getByName('settings.suppliers.products.show')->methods()[0]);
        $this->assertSame('PUT', Route::getRoutes()->getByName('settings.suppliers.products.update')->methods()[0]);
    }
}
