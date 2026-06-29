<?php

namespace Modules\Ecommerce\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Ecommerce\Models\Bundle;
use Modules\Ecommerce\Models\Product;
use Tests\TestCase;

class BundleTest extends TestCase
{
    use RefreshDatabase;

    public function test_bundle_calculates_original_total(): void
    {
        $bundle = Bundle::query()->create([
            'name' => 'Test Bundle',
            'slug' => 'test-bundle',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'status' => 'published',
        ]);

        $p1 = Product::factory()->create(['price' => 100]);
        $p2 = Product::factory()->create(['price' => 50]);

        $bundle->products()->attach([
            $p1->id => ['qty' => 1],
            $p2->id => ['qty' => 2],
        ]);

        $bundle->load('products');

        $this->assertEquals(200, $bundle->calculateOriginalTotal());
    }

    public function test_bundle_calculates_percentage_discount(): void
    {
        $bundle = Bundle::query()->create([
            'name' => 'Test',
            'slug' => 'test-pct',
            'discount_type' => 'percentage',
            'discount_value' => 20,
            'status' => 'published',
        ]);

        $p = Product::factory()->create(['price' => 100]);
        $bundle->products()->attach($p->id, ['qty' => 1]);
        $bundle->load('products');

        $this->assertEquals(80, $bundle->calculateBundlePrice());
        $this->assertEquals(20, $bundle->calculateSavings());
    }

    public function test_bundle_calculates_fixed_discount(): void
    {
        $bundle = Bundle::query()->create([
            'name' => 'Test',
            'slug' => 'test-fix',
            'discount_type' => 'fixed',
            'discount_value' => 30,
            'status' => 'published',
        ]);

        $p = Product::factory()->create(['price' => 100]);
        $bundle->products()->attach($p->id, ['qty' => 1]);
        $bundle->load('products');

        $this->assertEquals(70, $bundle->calculateBundlePrice());
    }

    public function test_bundle_price_never_goes_below_zero(): void
    {
        $bundle = Bundle::query()->create([
            'name' => 'Test',
            'slug' => 'test-floor',
            'discount_type' => 'fixed',
            'discount_value' => 999,
            'status' => 'published',
        ]);

        $p = Product::factory()->create(['price' => 10]);
        $bundle->products()->attach($p->id, ['qty' => 1]);
        $bundle->load('products');

        $this->assertEquals(0, $bundle->calculateBundlePrice());
    }
}
