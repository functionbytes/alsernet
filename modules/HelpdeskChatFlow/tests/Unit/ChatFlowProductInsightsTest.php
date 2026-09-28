<?php

namespace Modules\HelpdeskChatFlow\Tests\Unit;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Modules\HelpdeskChatFlow\Services\ChatFlowProductInsights;
use Modules\HelpdeskChatFlow\Tests\TestCase;

class ChatFlowProductInsightsTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * Realistic PrestashopProductQueryService::getProductAttributes()
     * payload: "Bota" with Talla × Color combinations, one out of stock,
     * one with low stock, one comfortably in stock.
     */
    private function bootAttributes(): array
    {
        return [
            'attributes' => [
                [
                    'group_id' => 1,
                    'group_name' => 'Talla',
                    'is_color' => false,
                    'values' => [
                        ['id' => 10, 'name' => '42', 'color' => null],
                        ['id' => 11, 'name' => '44', 'color' => null],
                    ],
                ],
                [
                    'group_id' => 2,
                    'group_name' => 'Color',
                    'is_color' => true,
                    'values' => [
                        ['id' => 20, 'name' => 'Marrón', 'color' => '#3d2b1f'],
                        ['id' => 21, 'name' => 'Negro', 'color' => '#000000'],
                    ],
                ],
            ],
            'combinations' => [
                [
                    'id' => 100, 'reference' => 'BOTA-42-MAR', 'price_delta' => 0.0,
                    'attribute_ids' => [10, 20], 'stock' => 0, 'in_stock' => false, 'image' => null,
                    'label' => '42 · Marrón', 'color' => '#3d2b1f',
                ],
                [
                    'id' => 101, 'reference' => 'BOTA-44-MAR', 'price_delta' => 0.0,
                    'attribute_ids' => [11, 20], 'stock' => 2, 'in_stock' => true, 'image' => null,
                    'label' => '44 · Marrón', 'color' => '#3d2b1f',
                ],
                [
                    'id' => 102, 'reference' => 'BOTA-44-NEG', 'price_delta' => 0.0,
                    'attribute_ids' => [11, 21], 'stock' => 15, 'in_stock' => true, 'image' => null,
                    'label' => '44 · Negro', 'color' => '#000000',
                ],
            ],
        ];
    }

    private function bootProduct(): array
    {
        return [
            'id' => 501, 'name' => 'Bota de montaña', 'sku' => 'BOTA-501', 'ean13' => null,
            'price' => 79.0, 'tax_rate' => 21.0, 'price_with_tax' => 95.59, 'price_original' => null,
            'has_discount' => false, 'description' => 'Bota impermeable de piel, suela antideslizante.',
            'image' => 'https://tienda.test/img/p/1.jpg', 'url' => 'https://tienda.test/501-bota',
            'stock' => 17, 'in_stock' => true, 'brand' => 'Alsernet', 'category' => 'Calzado',
        ];
    }

    private function bootSheet(?string $deliveryText = 'Entrega en 24/48h'): array
    {
        return [
            'product' => ['id' => 501, 'name' => 'Bota de montaña', 'reference' => 'BOTA-501', 'active' => true, 'has_combinations' => true, 'product_attribute_id' => 0],
            'stock' => [
                'web' => 17,
                'locations' => [
                    ['key' => 'pocomaco', 'label' => 'Almacén Pocomaco · A Coruña', 'units' => 10],
                    ['key' => 'capthaya', 'label' => 'Tienda Capitán Haya · Madrid', 'units' => 0],
                    ['key' => 'ddleon', 'label' => 'Tienda Diego de León · Madrid', 'units' => 0],
                    ['key' => 'tpvcor', 'label' => 'Tienda A Coruña', 'units' => 7],
                ],
                'total' => 17, 'has_breakdown' => true, 'supplier_days' => null,
            ],
            'availability' => [
                'in_stock' => true, 'delivery_code' => '48H', 'delivery_text' => $deliveryText,
                'no48h' => false, 'restock_date' => null, 'alerts_enabled' => true, 'alert_subscribed' => false,
            ],
            'pricing' => ['product_attribute_id' => 0, 'currency' => 'EUR', 'customer' => null, 'public' => [], 'tiers' => []],
        ];
    }

    public function test_variants_lists_options_with_and_without_stock(): void
    {
        $query = Mockery::mock();
        $query->shouldReceive('findById')->once()->with(501, 'es')->andReturn($this->bootProduct());
        $query->shouldReceive('getProductAttributes')->once()->with(501, 'es')->andReturn($this->bootAttributes());

        $catalog = Mockery::mock();
        $catalog->shouldReceive('productSheet')->once()->with(null, 501, 0)->andReturn($this->bootSheet());

        $insights = new ChatFlowProductInsights($catalog, $query);
        $result = $insights->variants(501, 'es');

        $this->assertSame(501, $result['product_id']);
        $this->assertSame('Bota de montaña', $result['title']);
        $this->assertSame('Entrega en 24/48h', $result['delivery_text']);
        $this->assertCount(3, $result['options']);

        $outOfStock = $result['options'][0];
        $this->assertSame('Talla: 42 · Color: Marrón', $outOfStock['label']);
        $this->assertFalse($outOfStock['available']);
        $this->assertSame('agotado', $outOfStock['stock_level']);
        $this->assertNull($outOfStock['delivery_text']);

        $lowStock = $result['options'][1];
        $this->assertSame('Talla: 44 · Color: Marrón', $lowStock['label']);
        $this->assertTrue($lowStock['available']);
        $this->assertSame('últimas unidades', $lowStock['stock_level']);
        $this->assertSame('Entrega en 24/48h', $lowStock['delivery_text']);

        $inStock = $result['options'][2];
        $this->assertSame('Talla: 44 · Color: Negro', $inStock['label']);
        $this->assertSame('disponible', $inStock['stock_level']);

        // Solo tiendas físicas: el almacén (pocomaco) queda fuera.
        $this->assertSame([
            ['store' => 'Tienda Capitán Haya · Madrid', 'available' => false],
            ['store' => 'Tienda Diego de León · Madrid', 'available' => false],
            ['store' => 'Tienda A Coruña', 'available' => true],
        ], $result['in_store_stock']);
    }

    public function test_variants_returns_empty_options_without_combinations(): void
    {
        $query = Mockery::mock();
        $query->shouldReceive('findById')->once()->with(777, 'es')->andReturn([
            'id' => 777, 'name' => 'Camiseta básica',
        ] + $this->bootProduct());
        $query->shouldReceive('getProductAttributes')->once()->with(777, 'es')
            ->andReturn(['attributes' => [], 'combinations' => []]);

        $catalog = Mockery::mock();
        $catalog->shouldReceive('productSheet')->once()->andReturn($this->bootSheet('Entrega en 24/48h'));

        $insights = new ChatFlowProductInsights($catalog, $query);
        $result = $insights->variants(777, 'es');

        $this->assertSame([], $result['options']);
    }

    public function test_variants_returns_null_when_product_not_found(): void
    {
        $query = Mockery::mock();
        $query->shouldReceive('findById')->once()->with(999, 'es')->andReturn(null);
        $query->shouldNotReceive('getProductAttributes');

        $insights = new ChatFlowProductInsights(Mockery::mock(), $query);

        $this->assertNull($insights->variants(999, 'es'));
    }

    public function test_variants_returns_null_without_prestashop_query_service(): void
    {
        $insights = new ChatFlowProductInsights(null, null);

        $this->assertNull($insights->variants(501));
    }

    public function test_option_for_matches_bare_size(): void
    {
        $query = Mockery::mock();
        $query->shouldReceive('getProductAttributes')->once()->with(501, 'es')->andReturn($this->bootAttributes());

        $catalog = Mockery::mock();
        $catalog->shouldReceive('productSheet')->once()->with(null, 501, 101)->andReturn($this->bootSheet('Entrega en 24/48h'));

        $insights = new ChatFlowProductInsights($catalog, $query);
        $option = $insights->optionFor(501, '44', 'es');

        $this->assertSame(101, $option['id_product_attribute']);
        $this->assertSame('Talla: 44 · Color: Marrón', $option['label']);
        $this->assertTrue($option['available']);
        $this->assertSame('últimas unidades', $option['stock_level']);
        $this->assertSame('Entrega en 24/48h', $option['delivery_text']);
    }

    public function test_option_for_matches_size_phrase_with_group_word(): void
    {
        $query = Mockery::mock();
        $query->shouldReceive('getProductAttributes')->once()->with(501, 'es')->andReturn($this->bootAttributes());

        $catalog = Mockery::mock();
        $catalog->shouldReceive('productSheet')->once()->with(null, 501, 101)->andReturn($this->bootSheet('Entrega en 24/48h'));

        $insights = new ChatFlowProductInsights($catalog, $query);
        // "talla" no aparece en ningún valor de atributo: se ignora y sigue
        // desambiguando solo por "44".
        $option = $insights->optionFor(501, 'talla 44', 'es');

        $this->assertSame(101, $option['id_product_attribute']);
    }

    public function test_option_for_matches_color_and_size_together(): void
    {
        $query = Mockery::mock();
        $query->shouldReceive('getProductAttributes')->once()->with(501, 'es')->andReturn($this->bootAttributes());

        $catalog = Mockery::mock();
        $catalog->shouldReceive('productSheet')->once()->with(null, 501, 102)->andReturn($this->bootSheet('Entrega en 24/48h'));

        $insights = new ChatFlowProductInsights($catalog, $query);
        $option = $insights->optionFor(501, 'negro 44', 'es');

        $this->assertSame(102, $option['id_product_attribute']);
        $this->assertSame('Talla: 44 · Color: Negro', $option['label']);
    }

    public function test_option_for_matches_letter_size_without_stock(): void
    {
        $attributes = $this->bootAttributes();
        $attributes['attributes'][0]['values'][] = ['id' => 12, 'name' => 'XL', 'color' => null];
        $attributes['combinations'][] = [
            'id' => 103, 'reference' => 'BOTA-XL', 'price_delta' => 0.0,
            'attribute_ids' => [12], 'stock' => 0, 'in_stock' => false, 'image' => null,
            'label' => 'XL', 'color' => null,
        ];

        $query = Mockery::mock();
        $query->shouldReceive('getProductAttributes')->once()->with(501, 'es')->andReturn($attributes);

        // Sin stock no se pide el plazo de entrega: ni una llamada a productSheet.
        $catalog = Mockery::mock();
        $catalog->shouldNotReceive('productSheet');

        $insights = new ChatFlowProductInsights($catalog, $query);
        $option = $insights->optionFor(501, 'XL', 'es');

        $this->assertSame(103, $option['id_product_attribute']);
        $this->assertFalse($option['available']);
        $this->assertSame('agotado', $option['stock_level']);
        $this->assertNull($option['delivery_text']);
    }

    public function test_option_for_returns_null_without_match(): void
    {
        $query = Mockery::mock();
        $query->shouldReceive('getProductAttributes')->once()->with(501, 'es')->andReturn($this->bootAttributes());

        $insights = new ChatFlowProductInsights(Mockery::mock(), $query);

        $this->assertNull($insights->optionFor(501, 'talla 99', 'es'));
    }

    public function test_option_for_returns_null_without_combinations(): void
    {
        $query = Mockery::mock();
        $query->shouldReceive('getProductAttributes')->once()->with(501, 'es')
            ->andReturn(['attributes' => [], 'combinations' => []]);

        $insights = new ChatFlowProductInsights(Mockery::mock(), $query);

        $this->assertNull($insights->optionFor(501, '44', 'es'));
    }

    public function test_compare_returns_compact_items_for_two_products(): void
    {
        $items = [
            [
                'id' => 501, 'name' => 'Bota de montaña', 'reference' => 'BOTA-501',
                'brand' => 'Alsernet', 'category' => 'Calzado', 'image' => 'https://tienda.test/img/501.jpg',
                'url' => 'https://tienda.test/501-bota', 'price_with_tax' => 95.59, 'price_original' => null,
                'has_discount' => false, 'stock' => 17, 'in_stock' => true, 'delivery_text' => 'Entrega en 24/48h',
                'variants' => [['group' => 'Talla', 'values' => ['42', '44']], ['group' => 'Color', 'values' => ['Marrón', 'Negro']]],
            ],
            [
                'id' => 602, 'name' => 'Bota urbana', 'reference' => 'BOTA-602',
                'brand' => 'Alsernet', 'category' => 'Calzado', 'image' => 'https://tienda.test/img/602.jpg',
                'url' => 'https://tienda.test/602-bota', 'price_with_tax' => 69.0, 'price_original' => 89.0,
                'has_discount' => true, 'stock' => 0, 'in_stock' => false, 'delivery_text' => null,
                'variants' => [['group' => 'Talla', 'values' => ['40', '41', '42']]],
            ],
        ];

        $catalog = Mockery::mock();
        $catalog->shouldReceive('compare')->once()->with([501, 602], 'es')->andReturn($items);

        $query = Mockery::mock();
        $query->shouldReceive('findByIds')->once()->with([501, 602], 'es')->andReturn([
            501 => ['description' => '<p>Bota impermeable de piel, suela antideslizante.</p>'],
            602 => ['description' => 'Bota urbana ligera para uso diario.'],
        ]);

        $insights = new ChatFlowProductInsights($catalog, $query);
        $result = $insights->compare([501, 602], 'es');

        $this->assertCount(2, $result);

        $this->assertSame(501, $result[0]['id']);
        $this->assertSame('Bota de montaña', $result[0]['title']);
        $this->assertSame('Alsernet', $result[0]['brand']);
        $this->assertSame(95.59, $result[0]['price']);
        $this->assertTrue($result[0]['available']);
        $this->assertSame('disponible', $result[0]['stock_level']);
        $this->assertSame('Entrega en 24/48h', $result[0]['delivery_text']);
        $this->assertSame('Bota impermeable de piel, suela antideslizante.', $result[0]['description']);
        $this->assertArrayNotHasKey('stock', $result[0]);

        $this->assertSame(602, $result[1]['id']);
        $this->assertFalse($result[1]['available']);
        $this->assertSame('agotado', $result[1]['stock_level']);
        $this->assertTrue($result[1]['has_discount']);
    }

    public function test_compare_returns_null_with_fewer_than_two_ids(): void
    {
        $catalog = Mockery::mock();
        $catalog->shouldNotReceive('compare');

        $insights = new ChatFlowProductInsights($catalog, Mockery::mock());

        $this->assertNull($insights->compare([501]));
    }

    public function test_compare_returns_null_without_catalog_service(): void
    {
        $insights = new ChatFlowProductInsights(null, Mockery::mock());

        $this->assertNull($insights->compare([501, 602]));
    }

    public function test_delivery_text_reads_bridge_availability(): void
    {
        $catalog = Mockery::mock();
        $catalog->shouldReceive('productSheet')->once()->with(null, 501, 101)->andReturn($this->bootSheet('Recogida en tienda mañana'));

        $insights = new ChatFlowProductInsights($catalog, Mockery::mock());

        $this->assertSame('Recogida en tienda mañana', $insights->deliveryText(501, 101));
    }

    public function test_delivery_text_returns_null_without_catalog_service(): void
    {
        $insights = new ChatFlowProductInsights(null, Mockery::mock());

        $this->assertNull($insights->deliveryText(501));
    }
}
