<?php

namespace Modules\Campaign\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Modules\Campaign\Listeners\UpdateTrackingLogOnBounce;
use Modules\Campaign\Models\Campaign;
use Modules\Campaign\Models\CampaignSubscriber;
use Modules\Campaign\Models\CampaignTrackingLog;
use Modules\CampaignSendingServers\Events\BounceDetected;
use Modules\CampaignSendingServers\Events\FeedbackLoopDetected;
use Tests\TestCase;

class BounceHandlerEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_bounce_detected_event_updates_tracking_log(): void
    {
        Event::fake([BounceDetected::class]);

        $campaign = Campaign::forceCreate(['name' => 'C', 'subject' => 'S', 'from_email' => 'a@a.com', 'from_name' => 'A', 'reply_to' => 'a@a.com']);
        $sub = CampaignSubscriber::create(['email' => 'to@example.com']);
        $log = CampaignTrackingLog::create([
            'uid' => (string) Str::uuid(),
            'campaign_id' => $campaign->id,
            'subscriber_id' => $sub->id,
            'email' => 'to@example.com',
            'message_id' => 'msg-123',
            'status' => 'sent',
        ]);

        $event = new BounceDetected('to@example.com', 'msg-123', true, 'bounce body');
        (new UpdateTrackingLogOnBounce)->handleBounce($event);

        $this->assertDatabaseHas('campaign_tracking_logs', [
            'id' => $log->id,
            'status' => CampaignTrackingLog::STATUS_BOUNCED,
        ]);
    }

    public function test_feedback_loop_detected_event_updates_tracking_log(): void
    {
        $campaign = Campaign::forceCreate(['name' => 'C', 'subject' => 'S', 'from_email' => 'a@a.com', 'from_name' => 'A', 'reply_to' => 'a@a.com']);
        $sub = CampaignSubscriber::create(['email' => 'to@example.com']);
        $log = CampaignTrackingLog::create([
            'uid' => (string) Str::uuid(),
            'campaign_id' => $campaign->id,
            'subscriber_id' => $sub->id,
            'email' => 'to@example.com',
            'message_id' => 'msg-456',
            'status' => 'sent',
        ]);

        $event = new FeedbackLoopDetected('to@example.com', 'msg-456', 'spam complaint');
        (new UpdateTrackingLogOnBounce)->handleFeedback($event);

        $this->assertDatabaseHas('campaign_tracking_logs', [
            'id' => $log->id,
            'status' => CampaignTrackingLog::STATUS_FEEDBACK_ABUSE,
        ]);
    }
}
