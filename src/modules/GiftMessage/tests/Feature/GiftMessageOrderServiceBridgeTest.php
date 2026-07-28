<?php

namespace Modules\GiftMessage\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Modules\GiftMessage\Services\GiftMessageOrderService;
use Modules\HelpdeskPrestashop\Support\HmacSigner;
use Tests\TestCase;

class GiftMessageOrderServiceBridgeTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('giftmessage.bridge_url', 'https://ps.test/modules/alsernetbridge/api.php');
        Config::set('giftmessage.bridge_secret', 'test-secret');
    }

    public function test_orders_with_message_signs_the_request_with_a_valid_hmac(): void
    {
        Http::fake(function ($request) {
            $body = $request->body();
            $timestamp = (int) $request->header('X-Alsernet-Timestamp')[0];
            $expectedSignature = HmacSigner::sign('test-secret', $timestamp, $body);

            $this->assertSame($expectedSignature, $request->header('X-Alsernet-Signature')[0]);
            $this->assertSame('giftmessage.orders_with_message', $request->header('X-Alsernet-Action')[0]);
            $this->assertSame('giftmessage.orders_with_message', json_decode($body, true)['action']);
            $this->assertArrayNotHasKey('lookup', json_decode($body, true));

            return Http::response(['ok' => true, 'data' => ['orders' => [['id_order' => 1]]]], 200);
        });

        $rows = app(GiftMessageOrderService::class)->ordersWithGiftMessage();

        $this->assertSame([['id_order' => 1]], $rows);
    }

    public function test_search_by_gestion_sends_gestion_ids_in_payload_and_no_idempotency_header(): void
    {
        Http::fake(function ($request) {
            $body = json_decode($request->body(), true);

            $this->assertSame(['102204020', '102204015'], $body['gestion_ids']);
            $this->assertFalse($request->hasHeader('X-Alsernet-Idempotency-Key'));

            return Http::response(['ok' => true, 'data' => ['orders' => []]], 200);
        });

        app(GiftMessageOrderService::class)->searchByGestion(['102204020', '102204015']);

        Http::assertSentCount(1);
    }

    public function test_returns_empty_array_without_calling_the_bridge_when_credentials_are_missing(): void
    {
        Config::set('giftmessage.bridge_url', '');
        Http::fake();

        $rows = app(GiftMessageOrderService::class)->ordersWithGiftMessage();

        $this->assertSame([], $rows);
        Http::assertNothingSent();
    }

    public function test_returns_empty_array_when_the_bridge_reports_ok_false(): void
    {
        Http::fake(fn () => Http::response(['ok' => false, 'error' => 'boom'], 200));

        $rows = app(GiftMessageOrderService::class)->ordersWithGiftMessage();

        $this->assertSame([], $rows);
    }

    public function test_returns_empty_array_when_the_bridge_is_unreachable(): void
    {
        Http::fake(fn () => throw new ConnectionException('timeout'));

        $rows = app(GiftMessageOrderService::class)->ordersWithGiftMessage();

        $this->assertSame([], $rows);
    }

    public function test_returns_empty_array_when_the_bridge_responds_with_an_error_status(): void
    {
        Http::fake(fn () => Http::response('Internal Server Error', 500));

        $rows = app(GiftMessageOrderService::class)->ordersWithGiftMessage();

        $this->assertSame([], $rows);
    }
}
