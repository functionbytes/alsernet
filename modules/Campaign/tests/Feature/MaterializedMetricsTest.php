<?php

namespace Modules\Campaign\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\Campaign\Events\CampaignMessageSent;
use Modules\Campaign\Listeners\UpdateCampaignMetrics;
use Modules\Campaign\Models\Campaign;
use Modules\Campaign\Models\CampaignSubscriber;
use Modules\Campaign\Models\CampaignTrackingLog;
use Modules\CampaignSendingServers\Models\SendingServer;
use Tests\TestCase;

class MaterializedMetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_sent_event_increments_sent_count(): void
    {
        Event::fake([CampaignMessageSent::class]);

        $campaign = Campaign::forceCreate([
            'name' => 'Test',
            'subject' => 'S',
            'from_email' => 'a@a.com',
            'from_name' => 'A',
            'reply_to' => 'a@a.com',
            'sent_count' => 0,
        ]);

        $log = new CampaignTrackingLog(['id' => 1]);
        $listener = new UpdateCampaignMetrics;
        $listener->handleSent(new CampaignMessageSent($campaign, $log));

        $this->assertSame(1, $campaign->fresh()->sent_count);
    }

    public function test_failed_tracking_increments_failed_count(): void
    {
        $campaign = Campaign::forceCreate([
            'name' => 'Test',
            'subject' => 'S',
            'from_email' => 'a@a.com',
            'from_name' => 'A',
            'reply_to' => 'a@a.com',
            'failed_count' => 0,
        ]);

        $server = SendingServer::forceCreate(['name' => 'Test', 'type' => 'smtp']);
        $subscriber = CampaignSubscriber::create(['email' => 'a@a.com', 'source' => 'test']);
        $campaign->trackMessage(['status' => 'failed', 'error' => 'err'], $subscriber, $server, 'msg-1');

        $this->assertSame(1, $campaign->fresh()->failed_count);
    }
}
