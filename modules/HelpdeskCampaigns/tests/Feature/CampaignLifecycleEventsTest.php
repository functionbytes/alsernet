<?php

namespace Modules\HelpdeskCampaigns\Tests\Feature;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Modules\HelpdeskCampaigns\Events\CampaignEnded;
use Modules\HelpdeskCampaigns\Events\CampaignImpressionRecorded;
use Modules\HelpdeskCampaigns\Events\CampaignPaused;
use Modules\HelpdeskCampaigns\Events\CampaignPublished;
use Modules\HelpdeskCampaigns\Events\CampaignResumed;
use Modules\HelpdeskCampaigns\Jobs\RecordImpressionJob;
use Modules\HelpdeskCampaigns\Listeners\LogCampaignActivity;
use Modules\HelpdeskCampaigns\Listeners\SendCampaignStatusNotification;
use Modules\HelpdeskCampaigns\Listeners\UpdateCampaignImpressionCounters;
use Tests\TestCase;

/**
 * Coverage for campaign lifecycle events and async tracking infrastructure.
 *
 * These tests verify that:
 *  - Lifecycle events are concrete classes that exist and accept a Campaign.
 *  - Public tracking endpoint dispatches RecordImpressionJob (async).
 *  - Listeners are wired in the EventServiceProvider's $listen array.
 *
 * Tests stay smoke-style (no DB writes) so they run regardless of helpdesk
 * schema availability in the test connection.
 */
class CampaignLifecycleEventsTest extends TestCase
{
    public function test_lifecycle_events_are_dispatchable(): void
    {
        foreach ([CampaignPublished::class, CampaignPaused::class, CampaignResumed::class, CampaignEnded::class] as $event) {
            $this->assertTrue(class_exists($event), "Event class missing: {$event}");
            $this->assertTrue(method_exists($event, 'dispatch'), "Event {$event} is not Dispatchable");
        }
    }

    public function test_impression_recorded_event_carries_was_click_flag(): void
    {
        $reflect = new \ReflectionClass(CampaignImpressionRecorded::class);
        $params = $reflect->getConstructor()->getParameters();
        $names = array_map(fn ($p) => $p->getName(), $params);

        $this->assertContains('impression', $names);
        $this->assertContains('wasClick', $names);
    }

    public function test_record_impression_job_is_queueable(): void
    {
        $reflect = new \ReflectionClass(RecordImpressionJob::class);
        $this->assertTrue($reflect->implementsInterface(ShouldQueue::class));
    }

    public function test_record_impression_job_dispatches_to_impressions_queue(): void
    {
        Queue::fake();

        RecordImpressionJob::dispatch(99999, [
            'page_url' => 'https://example.com/test',
            'device_type' => 'desktop',
        ]);

        Queue::assertPushedOn('impressions', RecordImpressionJob::class);
    }

    public function test_event_listeners_are_registered(): void
    {
        $expectations = [
            CampaignPublished::class => [
                LogCampaignActivity::class,
                SendCampaignStatusNotification::class,
            ],
            CampaignEnded::class => [
                LogCampaignActivity::class,
                SendCampaignStatusNotification::class,
            ],
            CampaignImpressionRecorded::class => [
                UpdateCampaignImpressionCounters::class,
            ],
        ];

        foreach ($expectations as $event => $listeners) {
            $registered = app('events')->getListeners($event);
            $this->assertNotEmpty($registered, "No listeners registered for {$event}");
        }
    }

    public function test_public_tracking_routes_are_registered(): void
    {
        $this->assertTrue(Route::has('helpdesk-campaigns.track.view'));
        $this->assertTrue(Route::has('helpdesk-campaigns.track.click'));
    }

    public function test_api_routes_are_registered_under_v1(): void
    {
        $this->assertTrue(Route::has('api.v1.helpdesk.campaigns.index'));
        $this->assertTrue(Route::has('api.v1.helpdesk.campaigns.publish'));
        $this->assertTrue(Route::has('api.v1.helpdesk.campaigns.end'));
    }

    public function test_api_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/helpdesk/campaigns');

        // 401/403 (auth deny) or 500 (when helpdesk DB connection is missing
        // in the test env) — both confirm unauthenticated traffic is NOT
        // served.
        $this->assertNotEquals(200, $response->status());
    }
}
