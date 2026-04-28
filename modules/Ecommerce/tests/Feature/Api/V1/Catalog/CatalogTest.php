<?php

namespace Modules\Ecommerce\Tests\Feature\Api\V1\Catalog;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Ecommerce\Models\Brand;
use Modules\Ecommerce\Models\Product;
use Modules\Ecommerce\Models\ProductCategory;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_index_returns_paginated_list(): void
    {
        Product::factory()->count(3)->create(['status' => 'published']);

        $response = $this->getJson('/api/v1/ecommerce/products');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data',
                'meta' => ['currentPage', 'perPage', 'total', 'lastPage'],
            ])
            ->assertJsonPath('success', true);
    }

    public function test_products_index_filters_by_brand(): void
    {
        $brand = Brand::factory()->create(['slug' => 'apple']);
        Product::factory()->count(2)->create(['brand_id' => $brand->id]);
        Product::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/ecommerce/products?filter[brand]=apple');

        $response->assertOk();
        $this->assertSame(2, $response->json('meta.total'));
    }

    public function test_categories_index_returns_published(): void
    {
        ProductCategory::factory()->count(3)->create(['status' => 'published', 'parent_id' => null]);

        $response = $this->getJson('/api/v1/ecommerce/categories');

        $response->assertOk()->assertJsonPath('success', true);
    }

    public function test_brands_index_returns_paginated(): void
    {
        Brand::factory()->count(5)->create(['status' => 'published']);

        $response = $this->getJson('/api/v1/ecommerce/brands');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data',
                'meta' => ['currentPage', 'total'],
            ]);
    }

    public function test_filters_endpoint_returns_structure(): void
    {
        Brand::factory()->count(2)->create(['status' => 'published']);
        ProductCategory::factory()->count(2)->create(['status' => 'published', 'parent_id' => null]);

        $response = $this->getJson('/api/v1/ecommerce/filters');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['brands', 'categories', 'priceRange' => ['min', 'max']],
            ]);
    }
}
