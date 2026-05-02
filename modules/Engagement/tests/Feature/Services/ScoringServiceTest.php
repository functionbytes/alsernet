<?php

namespace Modules\Engagement\Tests\Feature\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Engagement\Models\Event;
use Modules\Engagement\Models\VisitorScore;
use Modules\Engagement\Services\ScoringService;
use Modules\Helpdesk\Models\Inbox;
use Modules\HelpdeskLivechat\Models\Channels\Web;
use Tests\TestCase;

class ScoringServiceTest extends TestCase
{
    use RefreshDatabase;

    private ScoringService $service;

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
        $this->service = app(ScoringService::class);

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

    public function test_score_starts_cold_with_no_events(): void
    {
        $score = $this->service->getOrCreate('session-1', $this->inbox);

        $this->assertSame(0, $score->score);
        $this->assertSame('cold', $score->segment);
    }

    public function test_segment_progression_cold_warm_hot(): void
    {
        $this->assertSame('cold', VisitorScore::segmentFromScore(0));
        $this->assertSame('cold', VisitorScore::segmentFromScore(24));
        $this->assertSame('warm', VisitorScore::segmentFromScore(25));
        $this->assertSame('warm', VisitorScore::segmentFromScore(59));
        $this->assertSame('hot', VisitorScore::segmentFromScore(60));
        $this->assertSame('hot', VisitorScore::segmentFromScore(100));
    }

    public function test_recalculate_increases_score_with_purchase_events(): void
    {
        $token = 'session-purchase';

        Event::query()->create([
            'session_token' => $token,
            'inbox_id' => $this->inbox->id,
            'event_name' => 'purchase',
            'properties' => ['total' => 50],
            'occurred_at' => now()->subMinutes(5),
        ]);

        $score = $this->service->recalculate($token, $this->inbox->id);

        $this->assertGreaterThan(0, $score->score);
    }

    public function test_recalculate_clamps_score_to_100(): void
    {
        $token = 'session-many';

        for ($i = 0; $i < 20; $i++) {
            Event::query()->create([
                'session_token' => $token,
                'inbox_id' => $this->inbox->id,
                'event_name' => 'purchase',
                'properties' => ['total' => 100],
                'occurred_at' => now()->subMinutes($i),
            ]);
        }

        $score = $this->service->recalculate($token, $this->inbox->id);

        $this->assertLessThanOrEqual(100, $score->score);
    }
}
