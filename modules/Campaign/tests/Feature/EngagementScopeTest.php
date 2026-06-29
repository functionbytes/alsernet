<?php

namespace Modules\Campaign\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Campaign\Models\CampaignSubscriber;
use Modules\Campaign\Models\SubscriberEngagementScore;
use Tests\TestCase;

class EngagementScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_engagement_category_scope(): void
    {
        $sub = CampaignSubscriber::create(['email' => 'hot@example.com', 'source' => 'test']);
        SubscriberEngagementScore::create(['subscriber_id' => $sub->id, 'score' => 75]);

        $found = CampaignSubscriber::engagementCategory('hot')->first();
        $this->assertNotNull($found);
        $this->assertSame('hot@example.com', $found->email);
    }

    public function test_engagement_score_relation(): void
    {
        $sub = CampaignSubscriber::create(['email' => 'user@example.com', 'source' => 'test']);
        SubscriberEngagementScore::create(['subscriber_id' => $sub->id, 'score' => 45]);

        $this->assertNotNull($sub->fresh()->engagementScore);
        $this->assertSame(45, $sub->fresh()->engagementScore->score);
    }
}
