<?php

namespace Modules\HelpdeskLivechat\Tests\Unit;

use Mockery;
use Modules\HelpdeskLivechat\Services\Catalog\Drivers\BridgeCatalogDriver;
use Modules\HelpdeskPrestashop\Services\PrestashopContextService;
use Tests\TestCase;

/**
 * Catálogo del chat sobre la API del bridge: precio calculado por PrestaShop,
 * combinaciones (el widget abre la ficha) y disponibilidad.
 */
class BridgeCatalogDriverTest extends TestCase
{
    private function product(array $overrides = []): array
    {
        return array_merge([
            'id' => 43141,
            'name' => 'Estuche de limpieza MAXI',
            'price_with_tax' => 69.99,
            'final_price_with_tax' => 49.99,
            'in_stock' => true,
            'available_for_order' => true,
            'image' => 'https://shop.example/img/p/1.jpg',
            'url' => 'https://shop.example/43141-estuche',
            'id_product_attribute' => 0,
            'has_combinations' => false,
        ], $overrides);
    }

    public function test_search_prefers_prestashop_final_price_and_maps_fields(): void
    {
        $bridge = Mockery::mock(PrestashopContextService::class);
        $bridge->shouldReceive('searchProducts')->once()->with('estuche', 6, null)->andReturn([$this->product()]);

        $products = (new BridgeCatalogDriver($bridge))->search('estuche', 6);

        $this->assertCount(1, $products);
        $this->assertSame('43141', $products[0]->id);
        $this->assertSame(49.99, $products[0]->price);
        $this->assertSame('EUR', $products[0]->currency);
        $this->assertFalse($products[0]->hasCombinations);
        $this->assertTrue($products[0]->available);
    }

    public function test_falls_back_to_bridge_price_and_flags_combinations_and_stock(): void
    {
        $bridge = Mockery::mock(PrestashopContextService::class);
        $bridge->shouldReceive('getProductById')->once()->with(63720, null)->andReturn($this->product([
            'id' => 63720,
            'final_price_with_tax' => null,
            'price_with_tax' => 39.99,
            'has_combinations' => true,
            'id_product_attribute' => 12,
            'in_stock' => false,
        ]));

        $product = (new BridgeCatalogDriver($bridge))->find('63720');

        $this->assertSame(39.99, $product->price);
        $this->assertTrue($product->hasCombinations);
        $this->assertSame(12, $product->idProductAttribute);
        $this->assertFalse($product->available);
    }

    public function test_non_numeric_id_is_not_looked_up(): void
    {
        $bridge = Mockery::mock(PrestashopContextService::class);
        $bridge->shouldNotReceive('getProductById');

        $this->assertNull((new BridgeCatalogDriver($bridge))->find('1 OR 1=1'));
    }
}
