<?php

namespace Modules\Engagement\Tests\Feature\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Engagement\Models\CatalogProduct;
use Modules\Engagement\Models\Event;
use Modules\Engagement\Services\RecommenderService;
use Modules\Helpdesk\Models\Inbox;
use Modules\HelpdeskLivechat\Models\Channels\Web;
use Tests\TestCase;

class RecommenderServiceTest extends TestCase
{
    use RefreshDatabase;

    private RecommenderService $service;

    private Inbox $inbox;

    protected function beforeRefreshingDatabase(): void
    {
        if (! config()->has('database.connections.helpdesk')) {
            config()->set('database.connections.helpdesk', config('database.connections.sqlite'));
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(RecommenderService::class);

        $web = Web::create([
            'website_url' => 'https://x.com',
            'widget_color' => '#b10100',
            'widget_position' => 'right',
            'welcome_title' => 't',
            'welcome_tagline' => 'l',
        ]);

        $this->inbox = Inbox::create([
            'name' => 'Test',
            'channel_type' => 'web',
            'channel_id' => $web->id,
            'is_active' => true,
        ]);
    }

    public function test_returns_empty_when_no_catalog(): void
    {
        $recs = $this->service->recommend('session-1', null, 5, $this->inbox->id);

        $this->assertSame([], $recs);
    }

    public function test_returns_products_from_catalog(): void
    {
        CatalogProduct::factory()->count(10)->create(['inbox_id' => $this->inbox->id]);

        $recs = $this->service->recommend('session-1', null, 5, $this->inbox->id);

        $this->assertCount(5, $recs);
        $this->assertArrayHasKey('productId', $recs[0]);
        $this->assertArrayHasKey('name', $recs[0]);
        $this->assertArrayHasKey('price', $recs[0]);
    }

    public function test_excludes_recently_viewed_products(): void
    {
        $viewed = CatalogProduct::factory()->create([
            'inbox_id' => $this->inbox->id,
            'platform_product_id' => 'EXCLUDED-1',
        ]);

        CatalogProduct::factory()->count(3)->create(['inbox_id' => $this->inbox->id]);

        Event::query()->create([
            'session_token' => 'session-1',
            'inbox_id' => $this->inbox->id,
            'event_name' => 'product_view',
            'properties' => ['productId' => 'EXCLUDED-1'],
            'occurred_at' => now(),
        ]);

        $recs = $this->service->recommend('session-1', null, 10, $this->inbox->id);

        $ids = array_column($recs, 'productId');
        $this->assertNotContains('EXCLUDED-1', $ids);
    }

    public function test_only_returns_products_from_specified_inbox(): void
    {
        $otherWeb = Web::create([
            'website_url' => 'https://other.com',
            'widget_color' => '#fff',
            'widget_position' => 'right',
            'welcome_title' => 't',
            'welcome_tagline' => 'l',
        ]);
        $otherInbox = Inbox::create([
            'name' => 'Other',
            'channel_type' => 'web',
            'channel_id' => $otherWeb->id,
            'is_active' => true,
        ]);

        CatalogProduct::factory()->count(3)->create(['inbox_id' => $otherInbox->id]);
        CatalogProduct::factory()->count(2)->create(['inbox_id' => $this->inbox->id]);

        $recs = $this->service->recommend('session-1', null, 10, $this->inbox->id);

        $this->assertCount(2, $recs);
    }
}
