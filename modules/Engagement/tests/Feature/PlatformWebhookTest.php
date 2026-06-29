<?php

namespace Modules\Engagement\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Engagement\Models\PlatformIntegration;
use Modules\Helpdesk\Models\Inbox;
use Modules\HelpdeskLivechat\Models\Channels\Web;
use Tests\TestCase;

class PlatformWebhookTest extends TestCase
{
    use RefreshDatabase;

    private Web $web;

    private Inbox $inbox;

    private PlatformIntegration $prestashop;

    private string $secret;

    protected function beforeRefreshingDatabase(): void
    {
        if (! config()->has('database.connections.helpdesk')) {
            config()->set('database.connections.helpdesk', config('database.connections.sqlite'));
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->web = Web::create([
            'website_url' => 'https://example.com',
            'widget_color' => '#90bb13',
            'widget_position' => 'right',
            'welcome_title' => 'Hi',
            'welcome_tagline' => 'Hello',
        ]);

        $this->inbox = Inbox::create([
            'name' => 'Test',
            'channel_type' => 'web',
            'channel_id' => $this->web->id,
            'is_active' => true,
        ]);

        $this->secret = Str::random(64);

        $this->prestashop = PlatformIntegration::query()->create([
            'inbox_id' => $this->inbox->id,
            'platform' => 'prestashop',
            'webhook_secret' => $this->secret,
            'is_active' => true,
        ]);
    }

    private function url(string $platform, int $id): string
    {
        return route('engagement.sdk.webhook', ['platform' => $platform, 'integrationId' => $id]);
    }

    private function sign(string $body, ?string $secret = null): string
    {
        return hash_hmac('sha256', $body, $secret ?? $this->secret);
    }

    public function test_webhook_returns_404_for_invalid_integration(): void
    {
        $body = json_encode(['order_id' => 1]);

        $this->call(
            'POST',
            $this->url('prestashop', 9999),
            [],
            [],
            [],
            ['HTTP_X-Alsernet-Signature' => $this->sign($body), 'CONTENT_TYPE' => 'application/json'],
            $body,
        )->assertNotFound();
    }

    public function test_webhook_returns_404_when_platform_mismatch(): void
    {
        $body = json_encode(['order_id' => 1]);

        $this->call(
            'POST',
            $this->url('shopify', $this->prestashop->id),
            [],
            [],
            [],
            ['HTTP_X-Alsernet-Signature' => $this->sign($body), 'CONTENT_TYPE' => 'application/json'],
            $body,
        )->assertNotFound();
    }

    public function test_webhook_returns_401_with_invalid_signature(): void
    {
        $body = json_encode(['order_id' => 1]);

        $this->call(
            'POST',
            $this->url('prestashop', $this->prestashop->id),
            [],
            [],
            [],
            ['HTTP_X-Alsernet-Signature' => 'wrong-signature', 'CONTENT_TYPE' => 'application/json'],
            $body,
        )->assertUnauthorized();
    }

    public function test_webhook_returns_401_without_signature(): void
    {
        $body = json_encode(['order_id' => 1]);

        $this->call(
            'POST',
            $this->url('prestashop', $this->prestashop->id),
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $body,
        )->assertUnauthorized();
    }

    public function test_prestashop_webhook_records_purchase_event(): void
    {
        $body = json_encode([
            'order_id' => 42,
            'total' => 129.50,
            'customer' => ['email' => 'buyer@example.com', 'firstname' => 'John'],
        ]);

        $this->call(
            'POST',
            $this->url('prestashop', $this->prestashop->id),
            [],
            [],
            [],
            [
                'HTTP_X-Alsernet-Signature' => $this->sign($body),
                'HTTP_X-Alsernet-Topic' => 'actionValidateOrder',
                'CONTENT_TYPE' => 'application/json',
            ],
            $body,
        )->assertStatus(202);

        $this->assertDatabaseHas('engagement_events', [
            'inbox_id' => $this->inbox->id,
            'platform' => 'prestashop',
            'event_name' => 'purchase',
        ]);
    }

    public function test_shopify_webhook_uses_shopify_headers(): void
    {
        $integration = PlatformIntegration::query()->create([
            'inbox_id' => $this->inbox->id,
            'platform' => 'shopify',
            'webhook_secret' => $this->secret,
            'is_active' => true,
        ]);

        $body = json_encode(['email' => 'shop@example.com', 'total_price' => '99.99']);

        $this->call(
            'POST',
            $this->url('shopify', $integration->id),
            [],
            [],
            [],
            [
                'HTTP_X-Shopify-Hmac-Sha256' => $this->sign($body),
                'HTTP_X-Shopify-Topic' => 'orders/paid',
                'CONTENT_TYPE' => 'application/json',
            ],
            $body,
        )->assertStatus(202);

        $this->assertDatabaseHas('engagement_events', [
            'platform' => 'shopify',
            'event_name' => 'purchase',
        ]);
    }

    public function test_woocommerce_webhook_uses_woo_headers(): void
    {
        $integration = PlatformIntegration::query()->create([
            'inbox_id' => $this->inbox->id,
            'platform' => 'woocommerce',
            'webhook_secret' => $this->secret,
            'is_active' => true,
        ]);

        $body = json_encode(['id' => 7, 'billing' => ['email' => 'wc@example.com']]);

        $this->call(
            'POST',
            $this->url('woocommerce', $integration->id),
            [],
            [],
            [],
            [
                'HTTP_X-WC-Webhook-Signature' => $this->sign($body),
                'HTTP_X-WC-Webhook-Topic' => 'order.created',
                'CONTENT_TYPE' => 'application/json',
            ],
            $body,
        )->assertStatus(202);

        $this->assertDatabaseHas('engagement_events', [
            'platform' => 'woocommerce',
            'event_name' => 'purchase',
        ]);
    }

    public function test_inactive_integration_returns_404(): void
    {
        $this->prestashop->update(['is_active' => false]);

        $body = json_encode(['order_id' => 1]);

        $this->call(
            'POST',
            $this->url('prestashop', $this->prestashop->id),
            [],
            [],
            [],
            ['HTTP_X-Alsernet-Signature' => $this->sign($body), 'CONTENT_TYPE' => 'application/json'],
            $body,
        )->assertNotFound();
    }

    public function test_webhook_updates_last_event_at(): void
    {
        $this->assertNull($this->prestashop->last_event_at);

        $body = json_encode(['order_id' => 1]);

        $this->call(
            'POST',
            $this->url('prestashop', $this->prestashop->id),
            [],
            [],
            [],
            [
                'HTTP_X-Alsernet-Signature' => $this->sign($body),
                'HTTP_X-Alsernet-Topic' => 'actionValidateOrder',
                'CONTENT_TYPE' => 'application/json',
            ],
            $body,
        )->assertStatus(202);

        $this->assertNotNull($this->prestashop->fresh()->last_event_at);
    }
}
