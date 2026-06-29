<?php

namespace Modules\Engagement\Tests\Feature\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Engagement\Models\ConversionGoal;
use Modules\Engagement\Models\Event;
use Modules\Engagement\Services\ConversionMatcher;
use Modules\Helpdesk\Models\Inbox;
use Modules\HelpdeskLivechat\Models\Channels\Web;
use Tests\TestCase;

class ConversionMatcherTest extends TestCase
{
    use RefreshDatabase;

    private ConversionMatcher $matcher;

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
        $this->matcher = app(ConversionMatcher::class);

        $web = Web::create([
            'website_url' => 'https://x.com',
            'widget_color' => '#90bb13',
            'widget_position' => 'right',
            'welcome_title' => 't',
            'welcome_tagline' => 'l',
        ]);

        $this->inbox = Inbox::create([
            'name' => 'T',
            'channel_type' => 'web',
            'channel_id' => $web->id,
            'is_active' => true,
        ]);
    }

    public function test_matches_event_type_goal(): void
    {
        $goal = ConversionGoal::query()->create([
            'inbox_id' => $this->inbox->id,
            'name' => 'Compra',
            'type' => 'event',
            'event_name' => 'purchase',
            'is_active' => true,
        ]);

        $event = Event::query()->create([
            'session_token' => 'session-1',
            'inbox_id' => $this->inbox->id,
            'event_name' => 'purchase',
            'occurred_at' => now(),
        ]);

        $matched = $this->matcher->matchEvent($event);
        $this->assertContains($goal->id, $matched);
    }

    public function test_does_not_match_inactive_goal(): void
    {
        ConversionGoal::query()->create([
            'inbox_id' => $this->inbox->id,
            'name' => 'Compra',
            'type' => 'event',
            'event_name' => 'purchase',
            'is_active' => false,
        ]);

        $event = Event::query()->create([
            'session_token' => 'session-1',
            'inbox_id' => $this->inbox->id,
            'event_name' => 'purchase',
            'occurred_at' => now(),
        ]);

        $this->assertSame([], $this->matcher->matchEvent($event));
    }

    public function test_matches_url_visit_goal(): void
    {
        $goal = ConversionGoal::query()->create([
            'inbox_id' => $this->inbox->id,
            'name' => 'Thank you',
            'type' => 'url_visit',
            'url_pattern' => '/thank-you',
            'is_active' => true,
        ]);

        $event = Event::query()->create([
            'session_token' => 'session-1',
            'inbox_id' => $this->inbox->id,
            'event_name' => 'page_view',
            'properties' => ['url' => 'https://shop.com/thank-you?id=42'],
            'occurred_at' => now(),
        ]);

        $matched = $this->matcher->matchEvent($event);
        $this->assertContains($goal->id, $matched);
    }

    public function test_funnel_stats_calculates_drop_rate(): void
    {
        $goal = ConversionGoal::query()->create([
            'inbox_id' => $this->inbox->id,
            'name' => 'Funnel test',
            'type' => 'event',
            'event_name' => 'purchase',
            'funnel_steps' => ['view_product', 'add_to_cart', 'purchase'],
            'is_active' => true,
        ]);

        // 3 sesiones ven el producto, 2 añaden al carrito, 1 compra
        foreach (['s1', 's2', 's3'] as $s) {
            Event::query()->create([
                'session_token' => $s,
                'inbox_id' => $this->inbox->id,
                'event_name' => 'view_product',
                'occurred_at' => now()->subMinutes(10),
            ]);
        }
        foreach (['s1', 's2'] as $s) {
            Event::query()->create([
                'session_token' => $s,
                'inbox_id' => $this->inbox->id,
                'event_name' => 'add_to_cart',
                'occurred_at' => now()->subMinutes(5),
            ]);
        }
        Event::query()->create([
            'session_token' => 's1',
            'inbox_id' => $this->inbox->id,
            'event_name' => 'purchase',
            'occurred_at' => now(),
        ]);

        $stats = $this->matcher->funnelStats($goal);

        $this->assertCount(3, $stats);
        $this->assertSame(3, $stats[0]['visitors']);
        $this->assertSame(2, $stats[1]['visitors']);
        $this->assertSame(1, $stats[2]['visitors']);
    }
}
