<?php

namespace Modules\Supplier\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Modules\Supplier\Http\Controllers\Settings\Suppliers\SuppliersController;
use Tests\TestCase;

class SupplierRoutesTest extends TestCase
{
    public function test_controller_class_exists(): void
    {
        $this->assertTrue(class_exists(SuppliersController::class));
    }

    public function test_settings_suppliers_index_route_exists(): void
    {
        $this->assertTrue(Route::has('settings.suppliers.index'));
    }

    public function test_settings_suppliers_create_route_exists(): void
    {
        $this->assertTrue(Route::has('settings.suppliers.create'));
    }

    public function test_settings_suppliers_store_route_exists(): void
    {
        $this->assertTrue(Route::has('settings.suppliers.store'));
    }

    public function test_settings_suppliers_edit_route_exists(): void
    {
        $this->assertTrue(Route::has('settings.suppliers.edit'));
    }

    public function test_settings_suppliers_update_route_exists(): void
    {
        $this->assertTrue(Route::has('settings.suppliers.update'));
    }

    public function test_settings_suppliers_destroy_route_exists(): void
    {
        $this->assertTrue(Route::has('settings.suppliers.destroy'));
    }

    public function test_settings_suppliers_toggle_route_exists(): void
    {
        $this->assertTrue(Route::has('settings.suppliers.toggle'));
    }

    public function test_settings_suppliers_bulk_action_route_exists(): void
    {
        $this->assertTrue(Route::has('settings.suppliers.bulk-action'));
    }

    public function test_suppliers_index_route_exists(): void
    {
        $this->assertTrue(Route::has('suppliers.index'));
    }

    public function test_suppliers_show_route_exists(): void
    {
        $this->assertTrue(Route::has('suppliers.show'));
    }

    public function test_route_middleware_includes_auth(): void
    {
        $route = Route::getRoutes()->getByName('settings.suppliers.index');
        $this->assertNotNull($route);
        $this->assertContains('auth', $route->gatherMiddleware());
    }

    public function test_route_prefix_is_correct(): void
    {
        $route = Route::getRoutes()->getByName('settings.suppliers.index');
        $this->assertNotNull($route);
        $this->assertStringStartsWith('panel/setting/suppliers', $route->uri());
    }
}
