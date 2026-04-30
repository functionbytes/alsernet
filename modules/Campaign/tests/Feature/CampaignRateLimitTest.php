<?php

namespace Modules\Campaign\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Campaign\Library\CampaignRateTracker;
use Modules\Campaign\Models\Campaign;
use Modules\CampaignSendingServers\Library\Exception\RateLimitExceeded;
use Modules\CampaignSendingServers\Library\RateLimit;
use Tests\TestCase;

class CampaignRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_campaign_rate_tracker_allows_sends_under_limit(): void
    {
        $campaign = Campaign::forceCreate(['name' => 'T', 'subject' => 'S', 'from_email' => 'a@a.com', 'from_name' => 'A', 'reply_to' => 'a@a.com']);
        $tracker = new CampaignRateTracker($campaign->uid, [
            new RateLimit(5, 1, 'minute', 'test limit'),
        ]);

        $tracker->test();
        $this->assertTrue(true); // No lanza excepción
    }

    public function test_campaign_rate_tracker_blocks_when_limit_exceeded(): void
    {
        $campaign = Campaign::forceCreate(['name' => 'T', 'subject' => 'S', 'from_email' => 'a@a.com', 'from_name' => 'A', 'reply_to' => 'a@a.com']);
        $tracker = new CampaignRateTracker($campaign->uid, [
            new RateLimit(2, 1, 'minute', 'test limit'),
        ]);

        $tracker->increment();
        $tracker->increment();

        $this->expectException(RateLimitExceeded::class);
        $tracker->test();
    }

    public function test_campaign_rate_tracker_count_increments_correctly(): void
    {
        $campaign = Campaign::forceCreate(['name' => 'T', 'subject' => 'S', 'from_email' => 'a@a.com', 'from_name' => 'A', 'reply_to' => 'a@a.com']);
        $tracker = new CampaignRateTracker($campaign->uid, [
            new RateLimit(10, 1, 'minute', 'test limit'),
        ]);

        $tracker->increment();
        $tracker->increment();

        $this->assertGreaterThanOrEqual(2, $tracker->count());
    }

    public function test_campaign_rate_tracker_with_no_limits_is_unlimited(): void
    {
        $campaign = Campaign::forceCreate(['name' => 'T', 'subject' => 'S', 'from_email' => 'a@a.com', 'from_name' => 'A', 'reply_to' => 'a@a.com']);
        $tracker = CampaignRateTracker::forCampaign($campaign);

        $tracker->test();
        $this->assertEquals(0, $tracker->count());
    }
}
