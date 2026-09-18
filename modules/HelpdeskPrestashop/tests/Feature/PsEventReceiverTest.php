<?php

namespace Modules\HelpdeskPrestashop\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\TestResponse;
use Modules\Helpdesk\Models\Setting;
use Modules\HelpdeskPrestashop\Events\PsCartAbandoned;
use Modules\HelpdeskPrestashop\Events\PsOrderCreated;
use Tests\TestCase;

class PsEventReceiverTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['helpdesk'];

    private string $url = '/api/helpdeskprestashop/webhooks/event';

    protected function setUp(): void
    {
        parent::setUp();

        config(['helpdeskprestashop.webhook_secret' => 'test-secret']);
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Build a signed webhook request and return the response.
     *
     * @param  array<string, mixed>  $data
     */
    private function postSignedEvent(string $event, array $data, int $timestamp, string $secret = 'test-secret'): TestResponse
    {
        $body = json_encode(['action' => 'webhook.event', 'data' => $data]);
        $signature = hash_hmac('sha256', $timestamp.':'.$body, $secret);

        $server = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_ALSERNET_TIMESTAMP' => (string) $timestamp,
            'HTTP_X_ALSERNET_SIGNATURE' => $signature,
            'HTTP_X_ALSERNET_EVENT' => $event,
        ];

        return $this->call('POST', $this->url, [], [], [], $server, $body);
    }

    /**
     * Build a signed webhook request with a custom raw body (for signature-mismatch tests).
     */
    private function postWithHeaders(string $body, array $extraServer = []): TestResponse
    {
        $server = array_merge([
            'CONTENT_TYPE' => 'application/json',
        ], $extraServer);

        return $this->call('POST', $this->url, [], [], [], $server, $body);
    }

    // ─── Configuration guard ──────────────────────────────────────────────────

    public function test_returns_503_when_webhook_secret_not_configured(): void
    {
        config(['helpdeskprestashop.webhook_secret' => '']);
        Event::fake([PsOrderCreated::class, PsCartAbandoned::class]);

        $response = $this->postSignedEvent('order.created', ['order_id' => 1], time());

        $response->assertStatus(503);
        Event::assertNothingDispatched();
    }

    // ─── Timestamp validation ─────────────────────────────────────────────────

    public function test_returns_401_when_timestamp_header_missing(): void
    {
        Event::fake([PsOrderCreated::class, PsCartAbandoned::class]);

        $body = json_encode(['action' => 'webhook.event', 'data' => []]);
        $response = $this->postWithHeaders($body, [
            'HTTP_X_ALSERNET_SIGNATURE' => 'anything',
            'HTTP_X_ALSERNET_EVENT' => 'order.created',
            // No HTTP_X_ALSERNET_TIMESTAMP header → defaults to 0
        ]);

        $response->assertUnauthorized();
        Event::assertNothingDispatched();
    }

    public function test_returns_401_when_timestamp_is_zero(): void
    {
        Event::fake([PsOrderCreated::class, PsCartAbandoned::class]);

        $body = json_encode(['action' => 'webhook.event', 'data' => []]);
        $signature = hash_hmac('sha256', '0:'.$body, 'test-secret');
        $response = $this->postWithHeaders($body, [
            'HTTP_X_ALSERNET_TIMESTAMP' => '0',
            'HTTP_X_ALSERNET_SIGNATURE' => $signature,
            'HTTP_X_ALSERNET_EVENT' => 'order.created',
        ]);

        $response->assertUnauthorized();
        Event::assertNothingDispatched();
    }

    public function test_returns_401_when_timestamp_is_too_old(): void
    {
        Event::fake([PsOrderCreated::class, PsCartAbandoned::class]);

        $staleTimestamp = time() - 400;
        $response = $this->postSignedEvent('order.created', ['order_id' => 1], $staleTimestamp);

        $response->assertUnauthorized();
        Event::assertNothingDispatched();
    }

    // ─── Signature validation ─────────────────────────────────────────────────

    public function test_returns_401_when_signature_is_invalid(): void
    {
        Event::fake([PsOrderCreated::class, PsCartAbandoned::class]);

        $ts = time();
        $body = json_encode(['action' => 'webhook.event', 'data' => ['order_id' => 1]]);
        $response = $this->postWithHeaders($body, [
            'HTTP_X_ALSERNET_TIMESTAMP' => (string) $ts,
            'HTTP_X_ALSERNET_SIGNATURE' => 'totally-wrong-signature',
            'HTTP_X_ALSERNET_EVENT' => 'order.created',
        ]);

        $response->assertUnauthorized();
        Event::assertNothingDispatched();
    }

    // ─── Anti-replay (nonce) ──────────────────────────────────────────────────

    public function test_replaying_the_same_signed_request_returns_401(): void
    {
        Event::fake([PsOrderCreated::class, PsCartAbandoned::class]);

        $ts = time();
        $body = json_encode(['action' => 'webhook.event', 'data' => ['order_id' => 99]]);
        $signature = hash_hmac('sha256', $ts.':'.$body, 'test-secret');
        $server = [
            'HTTP_X_ALSERNET_TIMESTAMP' => (string) $ts,
            'HTTP_X_ALSERNET_SIGNATURE' => $signature,
            'HTTP_X_ALSERNET_EVENT' => 'order.created',
        ];

        $this->postWithHeaders($body, $server)->assertOk();

        // La misma petición capturada, reenviada dentro de la ventana de 300 s.
        $this->postWithHeaders($body, $server)->assertUnauthorized();
    }

    public function test_replay_with_a_different_idempotency_key_is_still_rejected(): void
    {
        Event::fake([PsOrderCreated::class, PsCartAbandoned::class]);

        $ts = time();
        $body = json_encode(['action' => 'webhook.event', 'data' => ['order_id' => 100]]);
        $signature = hash_hmac('sha256', $ts.':'.$body, 'test-secret');
        $server = [
            'HTTP_X_ALSERNET_TIMESTAMP' => (string) $ts,
            'HTTP_X_ALSERNET_SIGNATURE' => $signature,
            'HTTP_X_ALSERNET_EVENT' => 'order.created',
        ];

        $this->postWithHeaders($body, $server + ['HTTP_X_ALSERNET_IDEMPOTENCY_KEY' => 'key-a'])->assertOk();

        // La cabecera no está cubierta por la firma: cambiarla no puede
        // permitir el replay de la misma petición firmada.
        $this->postWithHeaders($body, $server + ['HTTP_X_ALSERNET_IDEMPOTENCY_KEY' => 'key-b'])
            ->assertUnauthorized();
    }

    public function test_reusing_an_idempotency_key_across_requests_is_rejected(): void
    {
        Event::fake([PsOrderCreated::class, PsCartAbandoned::class]);

        $ts = time();

        $bodyA = json_encode(['action' => 'webhook.event', 'data' => ['order_id' => 101]]);
        $this->postWithHeaders($bodyA, [
            'HTTP_X_ALSERNET_TIMESTAMP' => (string) $ts,
            'HTTP_X_ALSERNET_SIGNATURE' => hash_hmac('sha256', $ts.':'.$bodyA, 'test-secret'),
            'HTTP_X_ALSERNET_EVENT' => 'order.created',
            'HTTP_X_ALSERNET_IDEMPOTENCY_KEY' => 'shared-key',
        ])->assertOk();

        $bodyB = json_encode(['action' => 'webhook.event', 'data' => ['order_id' => 102]]);
        $this->postWithHeaders($bodyB, [
            'HTTP_X_ALSERNET_TIMESTAMP' => (string) $ts,
            'HTTP_X_ALSERNET_SIGNATURE' => hash_hmac('sha256', $ts.':'.$bodyB, 'test-secret'),
            'HTTP_X_ALSERNET_EVENT' => 'order.created',
            'HTTP_X_ALSERNET_IDEMPOTENCY_KEY' => 'shared-key',
        ])->assertUnauthorized();
    }

    public function test_distinct_signed_requests_both_pass(): void
    {
        Event::fake();

        $this->postSignedEvent('order.created', ['order_id' => 201], time())->assertOk();
        $this->postSignedEvent('order.created', ['order_id' => 202], time())->assertOk();

        Event::assertDispatchedTimes(PsOrderCreated::class, 2);
    }

    // ─── Integration toggle ───────────────────────────────────────────────────

    public function test_returns_204_and_dispatches_nothing_when_integration_disabled(): void
    {
        Setting::set('prestashop.integration_enabled', '0', 'integrations');
        // Acotar el fake a los eventos de dominio PS: assertNothingDispatched()
        // cuenta el total faked, y un Event::fake() sin argumentos incluiría los
        // eventos internos de framework (cache, eloquent, request), haciendo que
        // el aserto falle por ruido ajeno a la integración.
        Event::fake([PsOrderCreated::class, PsCartAbandoned::class]);

        $response = $this->postSignedEvent('order.created', [
            'order_id' => 42,
            'email' => 'customer@example.com',
            'total' => 150.00,
        ], time());

        $response->assertNoContent();
        Event::assertNothingDispatched();
    }

    public function test_dispatches_event_when_integration_enabled(): void
    {
        Setting::set('prestashop.integration_enabled', '1', 'integrations');
        Event::fake();

        $response = $this->postSignedEvent('order.created', [
            'order_id' => 42,
            'email' => 'customer@example.com',
            'total' => 150.00,
        ], time());

        $response->assertOk();
        $response->assertJson(['ok' => true]);
        Event::assertDispatched(PsOrderCreated::class);
    }

    // ─── Event dispatching ────────────────────────────────────────────────────

    public function test_order_created_event_dispatches_ps_order_created(): void
    {
        Event::fake();

        $response = $this->postSignedEvent('order.created', [
            'order_id' => 42,
            'email' => 'customer@example.com',
            'total' => 150.00,
        ], time());

        $response->assertOk();
        $response->assertJson(['ok' => true]);
        Event::assertDispatched(PsOrderCreated::class);
    }

    public function test_cart_abandoned_event_dispatches_ps_cart_abandoned(): void
    {
        Event::fake();

        $response = $this->postSignedEvent('cart.abandoned', [
            'cart_id' => 7,
            'email' => 'customer@example.com',
            'total' => 75.50,
        ], time());

        $response->assertOk();
        $response->assertJson(['ok' => true]);
        Event::assertDispatched(PsCartAbandoned::class);
    }

    public function test_unknown_event_returns_200_and_dispatches_nothing(): void
    {
        Event::fake([PsOrderCreated::class, PsCartAbandoned::class]);

        $response = $this->postSignedEvent('foo.bar', ['some' => 'data'], time());

        $response->assertOk();
        $response->assertJson(['ok' => true]);
        Event::assertNothingDispatched();
    }
}
