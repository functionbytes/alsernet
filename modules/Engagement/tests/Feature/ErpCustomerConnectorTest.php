<?php

namespace Modules\Engagement\Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Modules\Engagement\Connectors\ErpCustomerConnector;
use Modules\Engagement\Models\PlatformIntegration;
use Modules\Engagement\Tests\TestCase;
use Modules\Helpdesk\Models\Inbox;

class ErpCustomerConnectorTest extends TestCase
{
    private ErpCustomerConnector $connector;

    private PlatformIntegration $integration;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::store('array')->flush();

        $web = Web::create([
            'website_url' => 'https://example.com',
            'widget_color' => '#90bb13',
            'widget_position' => 'right',
            'welcome_title' => 'Hi',
            'welcome_tagline' => 'Hello',
        ]);

        $inbox = Inbox::create([
            'name' => 'Test',
            'channel_type' => 'web',
            'channel_id' => $web->id,
            'is_active' => true,
        ]);

        $this->integration = PlatformIntegration::query()->create([
            'inbox_id' => $inbox->id,
            'platform' => 'erp',
            'store_url' => 'https://manager.example.local/api/erp',
            'webhook_secret' => Str::random(64),
            'config' => ['auth_token' => Crypt::encryptString('TOKEN-TEST')],
            'is_active' => true,
        ]);

        $this->connector = app(ErpCustomerConnector::class);
    }

    public function test_resolves_external_id_by_email_via_search(): void
    {
        Http::fake([
            '*/customer/search*' => Http::response(['success' => true, 'data' => [['id' => 123]]], 200),
            '*/customer/123/orders' => Http::response(['success' => true, 'data' => [['id' => 1, 'total' => 50]]], 200),
        ]);

        $result = $this->connector->orders($this->integration, ['email' => 'x@y.com']);

        $this->assertTrue($result['ok']);
        Http::assertSentCount(2);
    }

    public function test_uses_external_id_directly_when_provided(): void
    {
        Http::fake([
            '*/customer/999/orders' => Http::response(['success' => true, 'data' => []], 200),
        ]);

        $result = $this->connector->orders($this->integration, ['external_id' => '999']);

        $this->assertTrue($result['ok']);
        Http::assertSentCount(1);
    }

    public function test_returns_error_when_search_yields_no_results(): void
    {
        Http::fake([
            '*/customer/search*' => Http::response(['success' => true, 'data' => []], 200),
        ]);

        $result = $this->connector->orders($this->integration, ['email' => 'notfound@x.com']);

        // resolveExternalId returns null → fetchByCustomer returns ok(null)
        $this->assertTrue($result['ok']);
        $this->assertNull($result['data']);

        // Should NOT call /customer/*/orders
        Http::assertSentCount(1);
    }

    public function test_returns_unsupported_for_returns_action(): void
    {
        $result = $this->connector->returns($this->integration, ['email' => 'x@y.com']);

        $this->assertFalse($result['ok']);
        $this->assertSame('unsupported', $result['error']);
    }

    public function test_authorization_header_contains_decrypted_token(): void
    {
        Http::fake([
            '*/customer/search*' => Http::response(['success' => true, 'data' => [['id' => 1]]], 200),
            '*/customer/1/orders' => Http::response(['success' => true, 'data' => []], 200),
        ]);

        $this->connector->orders($this->integration, ['email' => 'x@y.com']);

        Http::assertSent(function ($request) {
            return $request->header('Authorization')[0] === 'Bearer TOKEN-TEST';
        });
    }

    public function test_supports_debts_balance_loyaltypoints(): void
    {
        $this->assertTrue($this->connector->supports('debts'));
        $this->assertTrue($this->connector->supports('balance'));
        $this->assertTrue($this->connector->supports('loyaltyPoints'));
    }

    public function test_erp_does_not_support_returns_action(): void
    {
        $this->assertFalse($this->connector->supports('returns'));
    }

    public function test_order_detail_parses_customer_colon_order_format(): void
    {
        Http::fake([
            '*/customer/7/orders/42' => Http::response(['success' => true, 'data' => ['id' => 42, 'total' => 99]], 200),
        ]);

        $result = $this->connector->orderDetail($this->integration, '7:42');

        $this->assertTrue($result['ok']);
        $this->assertSame(42, $result['data']['id']);
    }
}
