<?php

namespace Modules\HelpdeskChatFlow\Tests\Unit;

use Modules\HelpdeskChatFlow\Services\ChatFlowOrderLookup;
use Tests\TestCase;

class ChatFlowOrderLookupTest extends TestCase
{
    public function test_returns_not_found_without_order_id(): void
    {
        $lookup = new ChatFlowOrderLookup(erp: null, ps: null);
        $result = $lookup->lookup(null, ['erp_id' => 1]);

        $this->assertFalse($result['found']);
    }

    public function test_normalizes_order_detail_from_erp(): void
    {
        $erp = new class
        {
            public function getOrderDetail(int $customerId, int $orderId): ?array
            {
                return [
                    'id' => $orderId,
                    'status' => 'En preparación',
                    'total' => '49,90 €',
                    'tracking' => 'ES123',
                ];
            }
        };

        $lookup = new ChatFlowOrderLookup(erp: $erp, ps: null);
        $result = $lookup->lookup('555', ['erp_id' => 10], 'erp');

        $this->assertTrue($result['found']);
        $this->assertSame(555, $result['order_id']);
        $this->assertSame('En preparación', $result['status']);
        $this->assertSame('49,90 €', $result['total']);
        $this->assertSame('ES123', $result['tracking']);
        $this->assertSame('erp', $result['source']);
    }

    public function test_falls_back_to_prestashop_when_erp_misses(): void
    {
        $erp = new class
        {
            public function getOrderDetail(int $customerId, int $orderId): ?array
            {
                return null;
            }
        };
        $ps = new class
        {
            public function getOrderDetail(int $orderId, ?string $email = null): ?array
            {
                return ['order' => ['reference' => 'PS-77', 'current_state' => 'Enviado']];
            }
        };

        $lookup = new ChatFlowOrderLookup(erp: $erp, ps: $ps);
        $result = $lookup->lookup('77', ['erp_id' => 10, 'email' => 'a@b.com'], 'auto');

        $this->assertTrue($result['found']);
        $this->assertSame('PS-77', $result['order_id']);
        $this->assertSame('Enviado', $result['status']);
        $this->assertSame('ps', $result['source']);
    }

    public function test_respects_explicit_source_selection(): void
    {
        $ps = new class
        {
            public function getOrderDetail(int $orderId, ?string $email = null): ?array
            {
                return ['id' => $orderId, 'state' => 'Pagado'];
            }
        };

        $lookup = new ChatFlowOrderLookup(erp: null, ps: $ps);
        $result = $lookup->lookup('88', ['email' => 'a@b.com'], 'ps');

        $this->assertTrue($result['found']);
        $this->assertSame('Pagado', $result['status']);
    }

    public function test_returns_not_found_when_services_unavailable(): void
    {
        $lookup = new ChatFlowOrderLookup(erp: null, ps: null);
        $result = $lookup->lookup('99', ['erp_id' => 1, 'email' => 'a@b.com'], 'auto');

        $this->assertFalse($result['found']);
    }
}
