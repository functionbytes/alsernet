<?php

namespace Modules\Remarketing\Tests\Feature\Connectors;

use Modules\Remarketing\Connectors\PrestaShop\PrestaShopConnector;
use Tests\TestCase;

/**
 * Expose protected normalizeOrder() for unit assertions.
 */
class TestablePrestaShopConnector extends PrestaShopConnector
{
    public function normalizeOrderPublic(array $order): array
    {
        return $this->normalizeOrder($order);
    }
}

class PrestaShopConnectorTest extends TestCase
{
    private TestablePrestaShopConnector $connector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->connector = new TestablePrestaShopConnector;
    }

    public function test_normalize_order_with_multiple_rows_returns_all_items(): void
    {
        $order = [
            'id' => 100,
            'reference' => 'ABC123',
            'total_paid_tax_incl' => '125.50',
            'total_paid_tax_excl' => '103.72',
            'total_products' => '103.72',
            'total_discounts_tax_incl' => '0.00',
            'total_shipping_tax_incl' => '5.00',
            'date_add' => '2024-01-15 10:30:00',
            'current_state' => '4',
            'id_customer' => '42',
            'associations' => [
                'order_rows' => [
                    'order_row' => [
                        ['product_id' => '1', 'product_quantity' => '2', 'unit_price_tax_incl' => '50.00', 'product_name' => 'Widget A', 'product_reference' => 'WA-001'],
                        ['product_id' => '2', 'product_quantity' => '1', 'unit_price_tax_incl' => '25.50', 'product_name' => 'Widget B', 'product_reference' => 'WB-002'],
                    ],
                ],
            ],
        ];

        $result = $this->connector->normalizeOrderPublic($order);

        $this->assertNotNull($result);
        $this->assertSame('100', $result['external_id']);
        $this->assertSame('ABC123', $result['order_number']);
        $this->assertCount(2, $result['items']);
        $this->assertSame('1', $result['items'][0]['external_product_id']);
        $this->assertSame(2, $result['items'][0]['quantity']);
        $this->assertSame(50.0, $result['items'][0]['price']);
        $this->assertSame('2', $result['items'][1]['external_product_id']);
    }

    public function test_normalize_order_with_single_row_as_associative_array_returns_one_item(): void
    {
        // PrestaShop returns a single row as an associative array, not a list.
        $order = [
            'id' => 101,
            'reference' => 'DEF456',
            'total_paid_tax_incl' => '50.00',
            'total_paid_tax_excl' => '41.32',
            'total_products' => '41.32',
            'total_discounts_tax_incl' => '0.00',
            'total_shipping_tax_incl' => '5.00',
            'date_add' => '2024-01-16 11:00:00',
            'current_state' => '2',
            'id_customer' => '5',
            'associations' => [
                'order_rows' => [
                    'order_row' => [
                        'product_id' => '5',
                        'product_quantity' => '1',
                        'unit_price_tax_incl' => '50.00',
                        'product_name' => 'Single Item',
                        'product_reference' => 'SI-001',
                    ],
                ],
            ],
        ];

        $result = $this->connector->normalizeOrderPublic($order);

        $this->assertNotNull($result);
        $this->assertCount(1, $result['items']);
        $this->assertSame('5', $result['items'][0]['external_product_id']);
        $this->assertSame(50.0, $result['items'][0]['price']);
    }

    public function test_normalize_order_with_no_rows_returns_empty_items(): void
    {
        $order = [
            'id' => 102,
            'reference' => 'GHI789',
            'total_paid_tax_incl' => '0.00',
            'total_paid_tax_excl' => '0.00',
            'total_products' => '0.00',
            'total_discounts_tax_incl' => '0.00',
            'total_shipping_tax_incl' => '0.00',
            'date_add' => '2024-01-17 12:00:00',
            'current_state' => '1',
            'id_customer' => '10',
            'associations' => [],
        ];

        $result = $this->connector->normalizeOrderPublic($order);

        $this->assertNotNull($result);
        $this->assertSame([], $result['items']);
    }

    public function test_normalize_order_maps_order_state_to_status(): void
    {
        $makeOrder = fn (string $state) => [
            'id' => 200,
            'reference' => 'REF',
            'total_paid_tax_incl' => '10.00',
            'total_paid_tax_excl' => '8.26',
            'total_products' => '8.26',
            'total_discounts_tax_incl' => '0.00',
            'total_shipping_tax_incl' => '2.00',
            'date_add' => '2024-01-18 09:00:00',
            'current_state' => $state,
            'id_customer' => '1',
            'associations' => [],
        ];

        $this->assertSame('completed', $this->connector->normalizeOrderPublic($makeOrder('4'))['status']);
        $this->assertSame('cancelled', $this->connector->normalizeOrderPublic($makeOrder('6'))['status']);
        $this->assertSame('refunded', $this->connector->normalizeOrderPublic($makeOrder('7'))['status']);
        $this->assertSame('pending', $this->connector->normalizeOrderPublic($makeOrder('1'))['status']);
    }

    public function test_normalize_order_totals_are_cast_to_floats(): void
    {
        $order = [
            'id' => 103,
            'reference' => 'JKL000',
            'total_paid_tax_incl' => '99.99',
            'total_paid_tax_excl' => '82.64',
            'total_products' => '82.64',
            'total_discounts_tax_incl' => '5.00',
            'total_shipping_tax_incl' => '10.00',
            'date_add' => '2024-02-01 08:00:00',
            'current_state' => '3',
            'id_customer' => '7',
            'associations' => [],
        ];

        $result = $this->connector->normalizeOrderPublic($order);

        $this->assertIsFloat($result['total']);
        $this->assertSame(99.99, $result['total']);
        $this->assertIsFloat($result['discount']);
        $this->assertSame(5.0, $result['discount']);
    }
}
