<?php

namespace Modules\Campaign\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Campaign\Http\Controllers\Api\SubscriberTimelineController;
use Modules\Campaign\Models\Campaign;
use Modules\Campaign\Models\CampaignSubscriber;
use Modules\Campaign\Models\CampaignTrackingLog;
use Tests\TestCase;

class SubscriberTimelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_timeline_includes_deliveries(): void
    {
        $sub = CampaignSubscriber::create(['email' => 'a@a.com', 'source' => 'test']);
        $campaign = Campaign::forceCreate(['name' => 'C', 'subject' => 'S', 'from_email' => 'a@a.com', 'from_name' => 'A', 'reply_to' => 'a@a.com']);
        CampaignTrackingLog::create(['campaign_id' => $campaign->id, 'subscriber_id' => $sub->id, 'email' => $sub->email, 'status' => 'sent', 'uid' => (string) Str::uuid()]);

        $controller = new SubscriberTimelineController;
        $response = $controller->timeline($sub->uid);
        $data = $response->getData(true);

        $this->assertSame('a@a.com', $data['subscriber']['email']);
        $types = collect($data['timeline'])->pluck('type')->all();
        $this->assertContains('delivery', $types);
    }
}
