<?php

namespace Modules\Engagement\Tests\Feature;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Modules\Engagement\Models\PlatformIntegration;
use Modules\Engagement\Services\CustomerDataOrchestrator;
use Modules\Engagement\Tests\TestCase;
use Modules\Helpdesk\Models\Inbox;

class CustomerDataOrchestratorTest extends TestCase
{
    private CustomerDataOrchestrator $orchestrator;

    private Inbox $inbox;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orchestrator = app(CustomerDataOrchestrator::class);

        $web = Web::create([
            'website_url' => 'https://example.com',
            'widget_color' => '#90bb13',
            'widget_position' => 'right',
            'welcome_title' => 'Hi',
            'welcome_tagline' => 'Hello',
        ]);

        $this->inbox = Inbox::create([
            'name' => 'Test',
            'channel_type' => 'web',
            'channel_id' => $web->id,
            'is_active' => true,
        ]);
    }

    private function createIntegration(string $platform, bool $active = true): PlatformIntegration
    {
        return PlatformIntegration::query()->create([
            'inbox_id' => $this->inbox->id,
            'platform' => $platform,
            'store_url' => 'https://shop.example.com',
            'webhook_secret' => Str::random(64),
            'is_active' => $active,
        ]);
    }

    public function test_integrations_for_inbox_returns_only_active(): void
    {
        $this->createIntegration('prestashop', active: true);
        $this->createIntegration('erp', active: false);

        $result = $this->orchestrator->integrationsForInbox($this->inbox->id);

        $this->assertCount(1, $result);
        $this->assertSame('prestashop', $result->first()->platform);
    }

    public function test_fetch_across_inbox_returns_results_per_platform(): void
    {
        $prestashop = $this->createIntegration('prestashop', active: true);
        $erp = $this->createIntegration('erp', active: true);

        // Stub both connectors via Http::fake so actual HTTP calls return ok
        Http::fake([
            '*alsernet_chat/api.php' => Http::response(['ok' => true, 'data' => []], 200),
            '*/customer/search*' => Http::response(['success' => true, 'data' => [['id' => 1]]], 200),
            '*/customer/1*' => Http::response(['success' => true, 'data' => []], 200),
        ]);

        $result = $this->orchestrator->fetchAcrossInbox($this->inbox->id, 'orders', ['email' => 'x@y.com']);

        $this->assertArrayHasKey($prestashop->id, $result);
        $this->assertArrayHasKey($erp->id, $result);
    }

    public function test_fetch_single_returns_unsupported_for_unknown_action(): void
    {
        $integration = $this->createIntegration('erp', active: true);

        $result = $this->orchestrator->fetchSingle($integration, 'returns', ['email' => 'x@y.com']);

        $this->assertFalse($result['ok']);
        $this->assertSame('unsupported', $result['error']);
    }

    public function test_fetch_across_inbox_skips_integrations_without_connector(): void
    {
        // 'shopify' integration without Http fake — if connector is called and
        // throws InvalidArgumentException the orchestrator must skip it gracefully
        $shopify = PlatformIntegration::query()->create([
            'inbox_id' => $this->inbox->id,
            'platform' => 'shopify',
            'store_url' => 'https://shop.example.com',
            'webhook_secret' => Str::random(64),
            'is_active' => true,
        ]);

        // shopify has no connector in ConnectorFactory — should return empty array
        // without throwing
        $result = $this->orchestrator->fetchAcrossInbox($this->inbox->id, 'orders', ['email' => 'x@y.com']);

        $this->assertIsArray($result);
        $this->assertArrayNotHasKey($shopify->id, $result);
    }

    public function test_invalidate_across_inbox_does_not_throw_for_active_integrations(): void
    {
        $this->createIntegration('prestashop', active: true);
        $this->createIntegration('erp', active: true);

        // Should not throw even with no cached data
        $this->orchestrator->invalidateAcrossInbox($this->inbox->id, ['email' => 'x@y.com']);

        $this->assertTrue(true); // reached without exception
    }

    public function test_invalidate_across_inbox_forces_fresh_fetch_after_cache_clear(): void
    {
        $this->createIntegration('prestashop', active: true);

        $lookup = ['email' => 'x@y.com'];

        Http::fake([
            '*alsernet_chat/api.php' => Http::response(['ok' => true, 'data' => ['orders' => []]], 200),
        ]);

        // Prime the cache
        $this->orchestrator->fetchAcrossInbox($this->inbox->id, 'orders', $lookup);

        // Invalidate so cache is cleared
        $this->orchestrator->invalidateAcrossInbox($this->inbox->id, $lookup);

        // Reset Http fake counter for the second batch
        Http::fake([
            '*alsernet_chat/api.php' => Http::response(['ok' => true, 'data' => ['orders' => []]], 200),
        ]);

        $this->orchestrator->fetchAcrossInbox($this->inbox->id, 'orders', $lookup);

        // After invalidation the connector makes a fresh HTTP call
        Http::assertSentCount(1);
    }

    public function test_fetch_single_returns_ok_for_supported_action(): void
    {
        $integration = $this->createIntegration('prestashop', active: true);

        Http::fake([
            '*alsernet_chat/api.php' => Http::response(['ok' => true, 'data' => ['orders' => []]], 200),
        ]);

        $result = $this->orchestrator->fetchSingle($integration, 'orders', ['email' => 'x@y.com']);

        $this->assertTrue($result['ok']);
        $this->assertSame('prestashop', $result['platform']);
    }
}
