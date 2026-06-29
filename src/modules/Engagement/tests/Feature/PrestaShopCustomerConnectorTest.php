<?php

namespace Modules\Engagement\Tests\Feature;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Modules\Engagement\Connectors\PrestaShopCustomerConnector;
use Modules\Engagement\Models\PlatformIntegration;
use Modules\Engagement\Tests\TestCase;
use Modules\Helpdesk\Models\Inbox;

class PrestaShopCustomerConnectorTest extends TestCase
{
    private PrestaShopCustomerConnector $connector;

    private PlatformIntegration $integration;

    private string $secret;

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

        $this->secret = Str::random(64);

        $this->integration = PlatformIntegration::query()->create([
            'inbox_id' => $inbox->id,
            'platform' => 'prestashop',
            'store_url' => 'https://shop.example.com',
            'webhook_secret' => $this->secret,
            'is_active' => true,
        ]);

        $this->connector = app(PrestaShopCustomerConnector::class);
    }

    public function test_profile_returns_data_on_successful_response(): void
    {
        Http::fake([
            '*alsernet_chat/api.php' => Http::response(['ok' => true, 'data' => ['id' => 5, 'email' => 'a@b.com']], 200),
        ]);

        $result = $this->connector->profile($this->integration, ['email' => 'a@b.com']);

        $this->assertTrue($result['ok']);
        $this->assertSame(5, $result['data']['id']);
        $this->assertSame('a@b.com', $result['data']['email']);
    }

    public function test_profile_returns_cached_on_second_call(): void
    {
        Http::fake([
            '*alsernet_chat/api.php' => Http::response(['ok' => true, 'data' => ['id' => 5]], 200),
        ]);

        $this->connector->profile($this->integration, ['email' => 'a@b.com']);
        $this->connector->profile($this->integration, ['email' => 'a@b.com']);

        Http::assertSentCount(1);
    }

    public function test_force_flag_bypasses_cache(): void
    {
        Http::fake([
            '*alsernet_chat/api.php' => Http::response(['ok' => true, 'data' => ['id' => 5]], 200),
        ]);

        $this->connector->profile($this->integration, ['email' => 'a@b.com']);
        $this->connector->profile($this->integration, ['email' => 'a@b.com'], force: true);

        Http::assertSentCount(2);
    }

    public function test_returns_error_on_401(): void
    {
        Http::fake([
            '*alsernet_chat/api.php' => Http::response([], 401),
        ]);

        $result = $this->connector->profile($this->integration, ['email' => 'a@b.com']);

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('401', $result['error']);
    }

    public function test_returns_error_on_connection_exception(): void
    {
        Http::fake(function () {
            throw new ConnectionException('timeout');
        });

        $result = $this->connector->profile($this->integration, ['email' => 'a@b.com']);

        $this->assertFalse($result['ok']);
        $this->assertNotNull($result['error']);
    }

    public function test_signature_is_valid_hmac_of_body(): void
    {
        Http::fake([
            '*alsernet_chat/api.php' => Http::response(['ok' => true, 'data' => []], 200),
        ]);

        $this->connector->profile($this->integration, ['email' => 'a@b.com']);

        Http::assertSent(function ($request) {
            $rawBody = $request->body();
            $expected = hash_hmac('sha256', $rawBody, $this->secret);

            return $request->header('X-Alsernet-Signature')[0] === $expected;
        });
    }

    public function test_supports_returns_true_for_supported_actions(): void
    {
        $this->assertTrue($this->connector->supports('orders'));
        $this->assertTrue($this->connector->supports('profile'));
        $this->assertTrue($this->connector->supports('returns'));
    }

    public function test_supports_returns_false_for_erp_only_actions(): void
    {
        $this->assertFalse($this->connector->supports('debts'));
        $this->assertFalse($this->connector->supports('balance'));
        $this->assertFalse($this->connector->supports('loyaltyPoints'));
    }

    public function test_invalidate_cache_clears_all_supported_actions(): void
    {
        $lookup = ['email' => 'a@b.com'];

        Http::fake([
            '*alsernet_chat/api.php' => Http::response(['ok' => true, 'data' => []], 200),
        ]);

        // Prime the cache for profile and orders
        $this->connector->profile($this->integration, $lookup);
        $this->connector->orders($this->integration, $lookup);

        // Clear the cache
        $this->connector->invalidateCache($this->integration, $lookup);

        // After invalidation the next call must hit HTTP again, not the cache
        Http::fake([
            '*alsernet_chat/api.php' => Http::response(['ok' => true, 'data' => ['fresh' => true]], 200),
        ]);

        $result = $this->connector->profile($this->integration, $lookup);

        Http::assertSentCount(1);
        $this->assertSame(['fresh' => true], $result['data']);
    }
}
