<?php

namespace Modules\Ecommerce\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\Models\Product;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    public function test_product_routes_are_registered(): void
    {
        $routes = Route::getRoutes();
        $names = collect($routes->getRoutesByName())->keys();

        $this->assertTrue($names->contains('ecommerce.products.index'));
        $this->assertTrue($names->contains('ecommerce.products.create'));
        $this->assertTrue($names->contains('ecommerce.products.store'));
        $this->assertTrue($names->contains('ecommerce.products.edit'));
        $this->assertTrue($names->contains('ecommerce.products.update'));
        $this->assertTrue($names->contains('ecommerce.products.destroy'));
    }

    public function test_product_model_exists(): void
    {
        $this->assertTrue(class_exists(Product::class));
    }
}
