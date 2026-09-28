<?php

namespace Modules\HelpdeskChatFlow\Tests\Unit;

use Mockery;
use Modules\HelpdeskChatFlow\Services\ChatFlowOrderLookup;
use Modules\HelpdeskChatFlow\Tests\TestCase;
use Modules\HelpdeskErp\Services\ErpContextService;
use Modules\HelpdeskPrestashop\Services\PrestashopContextService;

class ChatFlowOrderLookupTest extends TestCase
{
    public function test_returns_not_found_without_order_id(): void
    {
        $lookup = new ChatFlowOrderLookup(erp: null, ps: null);
        $result = $lookup->lookup(null, ['erp_id' => 1]);

        $this->assertFalse($result['found']);
    }

    public function test_returns_not_found_when_services_unavailable(): void
    {
        $lookup = new ChatFlowOrderLookup(erp: null, ps: null);
        $result = $lookup->lookup('99', ['erp_id' => 1, 'email' => 'a@b.com'], 'auto');

        $this->assertFalse($result['found']);
    }

    /**
     * Realistic `order.detail` payload as returned by
     * alsernetbridge/helpers/order.php::alsernet_order_detail() — state_name,
     * totals.total, tracking[] with tracking_url, lines[] and PII (address,
     * payments) that must never leak into the normalized result.
     */
    private function bridgeOrderDetailPayload(): array
    {
        return [
            'id' => 77,
            'reference' => 'XKBKNABJK',
            'customer_id' => 42,
            'customer_email' => 'cliente@example.com',
            'customer_firstname' => 'Ana',
            'customer_lastname' => 'García',
            'state_id' => 5,
            'state_name' => 'Enviado',
            'state_color' => '#32cd32',
            'currency' => 'EUR',
            'totals' => [
                'subtotal' => 40.0,
                'shipping' => 4.90,
                'discount' => 0.0,
                'tax' => 5.0,
                'total' => 49.90,
            ],
            'created_at' => '2026-09-20 10:15:00',
            'updated_at' => '2026-09-22 08:00:00',
            'lines' => [
                ['id' => 1, 'product_id' => 10, 'name' => 'Bota <b>de caza</b> talla 42', 'reference' => 'C112972', 'quantity' => 1, 'unit_price' => 39.90, 'total' => 39.90],
                ['id' => 2, 'product_id' => 11, 'name' => 'Calcetines técnicos', 'reference' => 'C900001', 'quantity' => 2, 'unit_price' => 5.00, 'total' => 10.00],
            ],
            'tracking' => [
                [
                    'tracking_number' => 'ES123456789',
                    'carrier_name' => 'GLS',
                    'weight' => 1.5,
                    'date' => '2026-09-21 09:00:00',
                    'tracking_url' => 'https://gls-group.eu/ES/es/seguimiento-de-paquetes?match=ES123456789',
                ],
            ],
            'payments' => [
                ['payment_method' => 'Tarjeta', 'transaction_id' => 'TXN1', 'amount' => 49.90, 'date_add' => '2026-09-20 10:16:00'],
            ],
            'history' => [
                ['state_id' => 1, 'state_name' => 'Pago aceptado', 'color' => '#ccc', 'date' => '2026-09-20 10:16:00'],
                ['state_id' => 5, 'state_name' => 'Enviado', 'color' => '#32cd32', 'date' => '2026-09-21 09:00:00'],
            ],
            'shipping_address_id' => 501,
            'billing_address_id' => null,
            'shipping_address' => [
                'address1' => 'Calle Falsa 123',
                'city' => 'A Coruña',
                'phone' => '600111222',
            ],
            'billing_address' => null,
        ];
    }

    public function test_normalizes_ps_order_detail_with_status_total_and_tracking_url(): void
    {
        $ps = Mockery::mock(PrestashopContextService::class);
        $ps->shouldReceive('getOrderDetail')
            ->once()
            ->with(77, 'cliente@example.com', null)
            ->andReturn($this->bridgeOrderDetailPayload());

        $lookup = new ChatFlowOrderLookup(erp: null, ps: $ps);
        $result = $lookup->lookup('77', ['email' => 'cliente@example.com'], 'ps');

        $this->assertTrue($result['found']);
        $this->assertSame(77, $result['order_id']);
        $this->assertSame('XKBKNABJK', $result['reference']);
        $this->assertSame('Enviado', $result['status']);
        $this->assertSame('2026-09-21 09:00:00', $result['status_date']);
        $this->assertSame('2026-09-20 10:15:00', $result['date']);
        $this->assertSame('49,90', $result['total']);
        $this->assertSame('EUR', $result['currency']);
        $this->assertSame('GLS', $result['carrier']);
        $this->assertSame('ES123456789', $result['tracking']);
        $this->assertSame('ES123456789', $result['tracking_number']);
        $this->assertSame('https://gls-group.eu/ES/es/seguimiento-de-paquetes?match=ES123456789', $result['tracking_url']);
        $this->assertSame('ps', $result['source']);

        // Items: max 5, HTML stripped from the name.
        $this->assertCount(2, $result['items']);
        $this->assertSame('Bota de caza talla 42', $result['items'][0]['name']);
        $this->assertSame(1, $result['items'][0]['quantity']);

        // PII never leaves the normalized result, not even inside `raw`.
        $this->assertArrayNotHasKey('shipping_address', $result['raw']);
        $this->assertArrayNotHasKey('billing_address', $result['raw']);
        $this->assertArrayNotHasKey('payments', $result['raw']);
        $this->assertArrayNotHasKey('customer_email', $result['raw']);
    }

    public function test_looks_up_by_reference_when_email_matches(): void
    {
        $ps = Mockery::mock(PrestashopContextService::class);
        $ps->shouldReceive('getOrderDetailByReference')
            ->once()
            ->with('XKBKNABJK', 'cliente@example.com', null)
            ->andReturn($this->bridgeOrderDetailPayload());

        $lookup = new ChatFlowOrderLookup(erp: null, ps: $ps);
        $result = $lookup->lookup('XKBKNABJK', ['email' => 'cliente@example.com'], 'ps');

        $this->assertTrue($result['found']);
        $this->assertSame('XKBKNABJK', $result['reference']);
    }

    public function test_reference_lookup_fails_closed_without_verified_email(): void
    {
        $ps = Mockery::mock(PrestashopContextService::class);
        $ps->shouldNotReceive('getOrderDetailByReference');
        $ps->shouldNotReceive('getOrderDetail');

        $lookup = new ChatFlowOrderLookup(erp: null, ps: $ps);
        $result = $lookup->lookup('XKBKNABJK', ['ps_id' => 42], 'ps');

        $this->assertFalse($result['found']);
    }

    public function test_reference_lookup_not_found_when_order_belongs_to_a_different_customer(): void
    {
        // The bridge itself enforces ownership (order.id_customer must match
        // the resolved customer from lookup.email) and returns null when the
        // reference belongs to someone else — simulated here directly.
        $ps = Mockery::mock(PrestashopContextService::class);
        $ps->shouldReceive('getOrderDetailByReference')
            ->once()
            ->with('XKBKNABJK', 'otro@example.com', null)
            ->andReturn(null);

        $lookup = new ChatFlowOrderLookup(erp: null, ps: $ps);
        $result = $lookup->lookup('XKBKNABJK', ['email' => 'otro@example.com'], 'ps');

        $this->assertFalse($result['found']);
    }

    /**
     * Realistic ERP payload as returned by
     * Erp\Services\ErpCustomerDataService::getOrderDetail() — numeric ESTADO,
     * expected_date (FPREVISTA), served_date (FSERVIDO), and PII (address,
     * phone) that must never leak into the normalized result.
     */
    private function erpOrderDetailPayload(): array
    {
        return [
            'id' => 555,
            'number' => 'P-555',
            'status' => 6, // Sirviéndose (config helpdeskErp.chat_codes.order_status)
            'date' => '2026-09-18',
            'expected_date' => '2026-09-25',
            'served_date' => null,
            'warehouse' => '1',
            'observations' => null,
            'phone' => '600111222',
            'payment_method' => 'Contado',
            'address' => 'Calle Falsa 123, 15008, A Coruña, A Coruña',
            'lines' => [
                ['name' => 'Chaleco de caza', 'qty' => 1, 'price' => 49.90, 'discount' => 0, 'vat' => 21],
            ],
            'total' => 49.90,
        ];
    }

    public function test_normalizes_erp_order_detail_with_expected_date_and_mapped_status(): void
    {
        $erp = Mockery::mock(ErpContextService::class);
        $erp->shouldReceive('getOrderDetail')
            ->once()
            ->with(10, 555)
            ->andReturn($this->erpOrderDetailPayload());

        $lookup = new ChatFlowOrderLookup(erp: $erp, ps: null);
        $result = $lookup->lookup('555', ['erp_id' => 10], 'erp');

        $this->assertTrue($result['found']);
        $this->assertSame(555, $result['order_id']);
        $this->assertSame('Sirviéndose', $result['status']);
        $this->assertSame('2026-09-18', $result['date']);
        $this->assertSame('2026-09-25', $result['expected_date']);
        $this->assertNull($result['shipped_date']);
        $this->assertSame('49,90', $result['total']);
        $this->assertSame('erp', $result['source']);
        $this->assertSame('Chaleco de caza', $result['items'][0]['name']);

        $this->assertArrayNotHasKey('address', $result['raw']);
        $this->assertArrayNotHasKey('phone', $result['raw']);
    }

    public function test_erp_status_without_known_mapping_returns_null_instead_of_inventing_one(): void
    {
        $erp = Mockery::mock(ErpContextService::class);
        $erp->shouldReceive('getOrderDetail')
            ->once()
            ->andReturn(['id' => 1, 'status' => 999, 'total' => 10]);

        $lookup = new ChatFlowOrderLookup(erp: $erp, ps: null);
        $result = $lookup->lookup('1', ['erp_id' => 10], 'erp');

        $this->assertTrue($result['found']);
        $this->assertNull($result['status']);
    }

    public function test_falls_back_to_prestashop_when_erp_misses(): void
    {
        $erp = Mockery::mock(ErpContextService::class);
        $erp->shouldReceive('getOrderDetail')->once()->andReturn(null);

        $ps = Mockery::mock(PrestashopContextService::class);
        $ps->shouldReceive('getOrderDetail')
            ->once()
            ->with(77, 'a@b.com', null)
            ->andReturn(['id' => 77, 'reference' => 'PS-77', 'state_name' => 'Enviado', 'totals' => ['total' => 10]]);

        $lookup = new ChatFlowOrderLookup(erp: $erp, ps: $ps);
        $result = $lookup->lookup('77', ['erp_id' => 10, 'email' => 'a@b.com'], 'auto');

        $this->assertTrue($result['found']);
        $this->assertSame('PS-77', $result['reference']);
        $this->assertSame('Enviado', $result['status']);
        $this->assertSame('ps', $result['source']);
    }

    public function test_recent_orders_from_prestashop(): void
    {
        $ps = Mockery::mock(PrestashopContextService::class);
        $ps->shouldReceive('getCustomerOrders')
            ->once()
            ->with('cliente@example.com', null, 5, 1)
            ->andReturn([
                'data' => [
                    ['id' => 90, 'reference' => 'AAA111', 'total' => 30.0, 'currency' => 'EUR', 'state_name' => 'Servido', 'created_at' => '2026-09-01 10:00:00'],
                    ['id' => 91, 'reference' => 'BBB222', 'total' => 15.5, 'currency' => 'EUR', 'state_name' => 'En preparación', 'created_at' => '2026-09-10 10:00:00'],
                ],
                'pagination' => ['limit' => 5, 'offset' => 0, 'total' => 2, 'has_more' => false],
            ]);

        $lookup = new ChatFlowOrderLookup(erp: null, ps: $ps);
        $orders = $lookup->recentOrders(['email' => 'cliente@example.com']);

        $this->assertCount(2, $orders);
        $this->assertSame(90, $orders[0]['order_id']);
        $this->assertSame('AAA111', $orders[0]['reference']);
        $this->assertSame('Servido', $orders[0]['status']);
        $this->assertSame('30,00', $orders[0]['total']);
        $this->assertSame('EUR', $orders[0]['currency']);
    }

    public function test_recent_orders_returns_empty_without_email_or_ps_id(): void
    {
        $ps = Mockery::mock(PrestashopContextService::class);
        $ps->shouldNotReceive('getCustomerOrders');

        $lookup = new ChatFlowOrderLookup(erp: null, ps: $ps);
        $orders = $lookup->recentOrders(['erp_id' => 5]);

        $this->assertSame([], $orders);
    }
}
