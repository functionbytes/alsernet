<?php

namespace Modules\HelpdeskPrestashop\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Modules\HelpdeskPrestashop\Services\PrestashopContextService;
use Tests\TestCase;

/**
 * Regresión IDOR: las acciones por-pedido del bridge NUNCA deben ejecutarse sin
 * lookup.email — sin él, el bridge resolvería el pedido solo por su id
 * secuencial sin verificar propiedad. El servicio ahora es fail-closed: con
 * email nulo o vacío devuelve null y no llama al bridge.
 */
class OrderOwnershipFailClosedTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'helpdeskprestashop.api_url' => 'http://localhost:8090/modules/alsernetbridge/api.php',
            'helpdeskprestashop.webhook_secret' => 'test-secret-for-hmac',
            'helpdeskprestashop.http_timeout' => 10,
        ]);
    }

    public function test_order_detail_without_email_returns_null_and_never_calls_bridge(): void
    {
        Http::fake();

        $result = app(PrestashopContextService::class)->getOrderDetail(123, null);

        $this->assertNull($result);
        Http::assertNothingSent();
    }

    public function test_order_detail_with_blank_email_returns_null_and_never_calls_bridge(): void
    {
        Http::fake();

        $result = app(PrestashopContextService::class)->getOrderDetail(123, '   ');

        $this->assertNull($result);
        Http::assertNothingSent();
    }

    public function test_start_return_without_email_returns_null_and_never_calls_bridge(): void
    {
        Http::fake();

        $result = app(PrestashopContextService::class)->startOrderReturn(
            123,
            [['order_detail_id' => 1, 'quantity' => 1]],
            null,
        );

        $this->assertNull($result);
        Http::assertNothingSent();
    }

    public function test_write_actions_without_email_return_null_and_never_call_bridge(): void
    {
        Http::fake();

        $service = app(PrestashopContextService::class);

        $this->assertNull($service->getOrderDocuments(123, null));
        $this->assertNull($service->changeOrderStatus(123, 5, false, null));
        $this->assertNull($service->setOrderAddress(123, 9, 'delivery', null));
        $this->assertNull($service->sendOrderEmail(123, 'invoice', null));
        $this->assertNull($service->addOrderNote(123, 'nota', 'Agente', null));
        $this->assertNull($service->setOrderTracking(123, 'TRACK-1', null, null));

        Http::assertNothingSent();
    }

    public function test_order_detail_with_email_sends_lookup_to_bridge(): void
    {
        Http::fake(['*' => Http::response(['ok' => true, 'data' => ['order' => ['id' => 123]]])]);

        app(PrestashopContextService::class)->getOrderDetail(123, 'cliente@example.com');

        Http::assertSent(function ($request): bool {
            $payload = $request->data();

            return ($payload['params']['lookup']['email'] ?? null) === 'cliente@example.com'
                || ($payload['lookup']['email'] ?? null) === 'cliente@example.com';
        });
    }
}
