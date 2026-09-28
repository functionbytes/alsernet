<?php

namespace Modules\Erp\Tests\Unit;

use Modules\Erp\Services\ErpCustomerDataService;
use Modules\Erp\Services\OCI8Service;
use Modules\Erp\Support\CustomerOrdersQuery;
use Tests\TestCase;

class CustomerOrdersAndDetailTest extends TestCase
{
    public function test_orders_are_sorted_before_rownum_is_applied(): void
    {
        [$sql, $bindings] = CustomerOrdersQuery::build(758143, '', '', '', 0, 10);

        // ORDER BY dentro y ROWNUM fuera: al revés Oracle corta N filas
        // cualesquiera (en la práctica las más antiguas) y luego las ordena.
        $this->assertMatchesRegularExpression('/SELECT \* FROM \(SELECT .* ORDER BY PEDIDOCLI_CENTRAL\.FPEDIDO DESC.*\) WHERE ROWNUM <= 11$/s', $sql);
        $this->assertSame(['id' => 758143], $bindings);
    }

    public function test_orders_offset_pages_over_the_sorted_set(): void
    {
        [$sql, $bindings] = CustomerOrdersQuery::build(5, '3', '2026-01-01', '2026-02-01', 20, 10);

        $this->assertStringContainsString('ORDER BY PEDIDOCLI_CENTRAL.FPEDIDO DESC', $sql);
        $this->assertStringContainsString('WHERE ROWNUM <= 31) WHERE rn > 20', $sql);
        $this->assertSame(['id' => 5, 'status' => '3', 'from' => '2026-01-01', 'to' => '2026-02-01'], $bindings);
    }

    public function test_orders_cache_key_is_versioned(): void
    {
        $this->assertStringStartsWith('customer:orders:v2:', CustomerOrdersQuery::cacheKey(1, '', '', '', 0, 10));
    }

    public function test_order_detail_is_scoped_to_the_customer(): void
    {
        $oci8 = $this->createMock(OCI8Service::class);
        $oci8->expects($this->once())
            ->method('query')
            ->with(
                $this->stringContains('IDPEDIDOCLI_CENTRAL = :id AND IDCLIENTE = :cid'),
                ['id' => 99, 'cid' => 7]
            )
            ->willReturn([]); // el pedido no es de ese cliente

        // Sin cabecera no se consultan líneas, forma de pago ni dirección.
        $this->assertNull((new ErpCustomerDataService($oci8))->getOrderDetail(99, 7));
    }
}
