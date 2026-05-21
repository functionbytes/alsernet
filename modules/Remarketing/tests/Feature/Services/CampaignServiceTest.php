<?php

namespace Modules\Remarketing\Tests\Feature\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Remarketing\Jobs\SendEmailJob;
use Modules\Remarketing\Models\Campaign;
use Modules\Remarketing\Models\Customer;
use Modules\Remarketing\Models\Message;
use Modules\Remarketing\Models\Segment;
use Modules\Remarketing\Models\Store;
use Modules\Remarketing\Models\Suppression;
use Modules\Remarketing\Services\CampaignService;
use Modules\Remarketing\Services\ConsentService;
use Modules\Remarketing\Services\SegmentService;
use Tests\TestCase;

class CampaignServiceTest extends TestCase
{
    use DatabaseTransactions;

    private CampaignService $service;

    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('remarketing_stores')) {
            $this->markTestSkipped('Migraciones del módulo Remarketing no aplicadas.');
        }

        $this->service = new CampaignService(
            new ConsentService,
            new SegmentService,
        );
        $this->store = $this->createStore();
    }

    public function test_schedule_marks_campaign_as_scheduled_with_date(): void
    {
        Queue::fake();
        $campaign = $this->createCampaign(['status' => 'draft']);
        $scheduledAt = Carbon::now()->addDays(2);

        $this->service->schedule($campaign, $scheduledAt);

        $campaign->refresh();
        $this->assertEquals('scheduled', $campaign->status);
        $this->assertEquals(
            $scheduledAt->toDateTimeString(),
            $campaign->scheduled_at->toDateTimeString()
        );
    }

    public function test_send_dispatches_send_email_job_per_recipient(): void
    {
        Queue::fake();

        // Use a segment that matches our 2 subscribed customers
        $tag = 'st-'.substr(uniqid(), 0, 5);
        $segment = $this->createSegment([
            'conditions' => [
                'operator' => 'AND',
                'conditions' => [
                    ['type' => 'property', 'field' => 'status', 'operator' => 'equals', 'value' => 'subscribed'],
                    ['type' => 'property', 'field' => 'locale', 'operator' => 'equals', 'value' => $tag],
                ],
            ],
        ]);
        $campaign = $this->createCampaign(['segment_id' => $segment->id]);

        $this->createSubscribedCustomer(['locale' => $tag]);
        $this->createSubscribedCustomer(['locale' => $tag]);

        $dispatched = $this->service->send($campaign);

        $this->assertEquals(2, $dispatched);
        Queue::assertPushed(SendEmailJob::class, 2);
        $campaign->refresh();
        $this->assertEquals('sending', $campaign->status);
        $this->assertEquals(2, $campaign->recipients_total);
    }

    public function test_send_skips_suppressed_emails(): void
    {
        Queue::fake();

        $tag = 'sp-'.substr(uniqid(), 0, 5);
        $segment = $this->createSegment([
            'conditions' => [
                'operator' => 'AND',
                'conditions' => [
                    ['type' => 'property', 'field' => 'status', 'operator' => 'equals', 'value' => 'subscribed'],
                    ['type' => 'property', 'field' => 'locale', 'operator' => 'equals', 'value' => $tag],
                ],
            ],
        ]);
        $campaign = $this->createCampaign(['segment_id' => $segment->id]);

        $this->createSubscribedCustomer(['locale' => $tag]);
        $suppressedCustomer = $this->createSubscribedCustomer(['locale' => $tag]);

        Suppression::query()->create([
            'store_id' => $this->store->id,
            'email' => $suppressedCustomer->email,
            'reason' => 'hard_bounce',
        ]);

        $dispatched = $this->service->send($campaign);

        $this->assertEquals(1, $dispatched);
        Queue::assertPushed(SendEmailJob::class, 1);
    }

    public function test_cancel_marks_campaign_cancelled(): void
    {
        Queue::fake();
        $campaign = $this->createCampaign(['status' => 'sending']);

        $this->service->cancel($campaign);

        $campaign->refresh();
        $this->assertEquals('cancelled', $campaign->status);
        $this->assertNotNull($campaign->completed_at);
    }

    public function test_recalculate_stats_aggregates_messages(): void
    {
        $campaign = $this->createCampaign();
        $customer = $this->createSubscribedCustomer();

        Message::query()->create([
            'store_id' => $this->store->id,
            'customer_id' => $customer->id,
            'campaign_id' => $campaign->id,
            'email' => $customer->email,
            'subject' => 'Test',
            'status' => 'sent',
            'sent_at' => now(),
            'open_token' => Str::random(32),
            'click_token' => Str::random(32),
        ]);
        Message::query()->create([
            'store_id' => $this->store->id,
            'customer_id' => $customer->id,
            'campaign_id' => $campaign->id,
            'email' => 'other-'.uniqid().'@example.com',
            'subject' => 'Test',
            'status' => 'bounced',
            'bounced_at' => now(),
            'open_token' => Str::random(32),
            'click_token' => Str::random(32),
        ]);
        Message::query()->create([
            'store_id' => $this->store->id,
            'customer_id' => $customer->id,
            'campaign_id' => $campaign->id,
            'email' => 'clicked-'.uniqid().'@example.com',
            'subject' => 'Test',
            'status' => 'clicked',
            'sent_at' => now(),
            'clicked_at' => now(),
            'opened_at' => now(),
            'open_token' => Str::random(32),
            'click_token' => Str::random(32),
        ]);

        $this->service->recalculateStats($campaign);

        $campaign->refresh();
        $this->assertEquals(1, $campaign->sent);
        $this->assertEquals(1, $campaign->bounced);
        $this->assertEquals(1, $campaign->clicked);
    }

    private function createStore(array $attributes = []): Store
    {
        return Store::query()->create(array_merge([
            'user_id' => $this->testUser?->id ?? User::factory()->create()->id,
            'platform' => 'manual',
            'name' => 'Test Store '.uniqid(),
            'domain' => 'test-'.uniqid().'.example.com',
            'status' => 'active',
            'webhook_token' => Str::random(64),
        ], $attributes));
    }

    private function createSubscribedCustomer(array $attributes = []): Customer
    {
        return Customer::query()->create(array_merge([
            'store_id' => $this->store->id,
            'email' => 'sub-'.uniqid().'@example.com',
            'email_hash' => hash('sha256', uniqid()),
            'status' => 'subscribed',
            'consent_marketing' => true,
        ], $attributes));
    }

    private function createCampaign(array $attributes = []): Campaign
    {
        return Campaign::query()->create(array_merge([
            'store_id' => $this->store->id,
            'name' => 'Test Campaign '.uniqid(),
            'subject' => 'Subject '.uniqid(),
            'from_name' => 'Test Sender',
            'from_email' => 'sender@example.com',
            'status' => 'draft',
        ], $attributes));
    }

    private function createSegment(array $attributes = []): Segment
    {
        return Segment::query()->create(array_merge([
            'store_id' => $this->store->id,
            'name' => 'Test Segment '.uniqid(),
            'type' => 'dynamic',
            'conditions' => [
                'operator' => 'AND',
                'conditions' => [
                    ['type' => 'property', 'field' => 'status', 'operator' => 'equals', 'value' => 'subscribed'],
                ],
            ],
            'member_count' => 0,
        ], $attributes));
    }
}
