<?php

namespace Modules\Ecommerce\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Modules\Ecommerce\Jobs\SendCampaignJob;
use Modules\Ecommerce\Models\Customer;
use Modules\Ecommerce\Models\EmailCampaign;
use Modules\Ecommerce\Services\CustomerSegmentService;
use Tests\TestCase;

class EmailCampaignTest extends TestCase
{
    use RefreshDatabase;

    public function test_segment_service_counts_all_active_customers(): void
    {
        Customer::factory()->count(3)->create(['status' => 'active']);
        $count = app(CustomerSegmentService::class)->countSegment('all');
        $this->assertGreaterThanOrEqual(3, $count);
    }

    public function test_send_campaign_job_can_be_dispatched(): void
    {
        Queue::fake();
        $campaign = EmailCampaign::query()->create([
            'name' => 'Test',
            'subject' => 'Test',
            'content' => '<p>Hi</p>',
            'segment' => 'all',
            'status' => 'draft',
        ]);

        SendCampaignJob::dispatch($campaign->id);

        Queue::assertPushed(SendCampaignJob::class);
    }

    public function test_email_tracking_open_returns_pixel(): void
    {
        $response = $this->get('/api/v1/ecommerce/email-tracking/open/invalid-token');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/gif');
    }

    public function test_email_tracking_click_redirects(): void
    {
        $response = $this->get('/api/v1/ecommerce/email-tracking/click/invalid-token?url='.urlencode('https://example.com'));

        $response->assertRedirect('https://example.com');
    }
}
