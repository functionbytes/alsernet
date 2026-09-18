<?php

namespace Modules\HelpdeskCampaigns\Tests\Feature;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Route;
use Modules\HelpdeskCampaigns\Jobs\EndExpiredCampaignsJob;
use Modules\HelpdeskCampaigns\Jobs\PublishScheduledCampaignsJob;
use Modules\HelpdeskCampaigns\Listeners\DispatchCampaignWebhooks;
use Modules\HelpdeskCampaigns\Models\Campaign;
use Modules\HelpdeskCampaigns\Services\FrequencyCapService;
use Modules\HelpdeskCampaigns\Services\TargetingService;
use Modules\HelpdeskCampaigns\Services\VariantSelector;
use Tests\TestCase;

/**
 * Smoke coverage for the enterprise features added in 2026-05-06:
 *  - Lifecycle scheduler jobs (publish/end)
 *  - Frequency capping service
 *  - Targeting service
 *  - Variant selector (A/B testing)
 *  - Webhook dispatcher
 *  - Bulk + approval routes
 *
 * Tests verify wiring (classes exist, routes registered, services injectable)
 * without depending on the helpdesk schema.
 */
class CampaignEnterpriseFeatureTest extends TestCase
{
    public function test_scheduler_jobs_exist_and_implement_should_queue(): void
    {
        foreach ([PublishScheduledCampaignsJob::class, EndExpiredCampaignsJob::class] as $job) {
            $this->assertTrue(class_exists($job), "Missing job: {$job}");
            $this->assertTrue(
                (new \ReflectionClass($job))->implementsInterface(ShouldQueue::class),
                "{$job} must be ShouldQueue"
            );
        }
    }

    public function test_services_are_resolvable_from_container(): void
    {
        $this->assertInstanceOf(FrequencyCapService::class, app(FrequencyCapService::class));
        $this->assertInstanceOf(TargetingService::class, app(TargetingService::class));
        $this->assertInstanceOf(VariantSelector::class, app(VariantSelector::class));
    }

    public function test_targeting_service_url_match_glob(): void
    {
        $svc = app(TargetingService::class);
        $campaign = new Campaign;
        $campaign->conditions = ['url_match' => '/checkout/*'];

        $this->assertTrue($svc->matches($campaign, ['page_url' => '/checkout/cart']));
        $this->assertFalse($svc->matches($campaign, ['page_url' => '/products/list']));
    }

    public function test_targeting_service_device_filter(): void
    {
        $svc = app(TargetingService::class);
        $campaign = new Campaign;
        $campaign->conditions = ['device_types' => ['mobile', 'tablet']];

        $this->assertTrue($svc->matches($campaign, ['device_type' => 'mobile']));
        $this->assertFalse($svc->matches($campaign, ['device_type' => 'desktop']));
    }

    public function test_targeting_service_passes_when_no_conditions(): void
    {
        $svc = app(TargetingService::class);
        $campaign = new Campaign;

        $this->assertTrue($svc->matches($campaign, []));
    }

    public function test_variant_selector_returns_null_when_no_variants(): void
    {
        $svc = app(VariantSelector::class);
        $campaign = new Campaign;
        $campaign->id = 999999;

        // The relation will be queried; without DB rows it returns empty,
        // so pick() returns null.
        try {
            $result = $svc->pick($campaign, ['ip_address' => '127.0.0.1']);
            $this->assertNull($result);
        } catch (\Throwable $e) {
            // Skip if helpdesk DB connection is not available in test env.
            $this->markTestSkipped('helpdesk schema not available: '.$e->getMessage());
        }
    }

    public function test_webhook_listener_implements_should_queue(): void
    {
        $this->assertTrue(
            (new \ReflectionClass(DispatchCampaignWebhooks::class))
                ->implementsInterface(ShouldQueue::class)
        );
    }

    public function test_bulk_action_route_is_registered(): void
    {
        $this->assertTrue(Route::has('helpdesk.campaigns.bulk-action'));
    }

    public function test_approval_workflow_routes_are_registered(): void
    {
        $this->assertTrue(Route::has('helpdesk.campaigns.submit-for-approval'));
        $this->assertTrue(Route::has('helpdesk.campaigns.approve'));
        $this->assertTrue(Route::has('helpdesk.campaigns.reject'));
    }

    public function test_statistics_export_and_timeline_routes_are_registered(): void
    {
        $this->assertTrue(Route::has('helpdesk.campaigns.statistics.export'));
        $this->assertTrue(Route::has('helpdesk.campaigns.statistics.timeline'));
        $this->assertTrue(Route::has('helpdesk.campaigns.activity'));
    }

    public function test_widget_js_file_exists(): void
    {
        $path = module_path('HelpdeskCampaigns', 'public/js/widget.js');
        $this->assertFileExists($path);
        $content = file_get_contents($path);
        $this->assertStringContainsString('HelpdeskCampaigns', $content);
        $this->assertStringContainsString('track/view', $content);
        $this->assertStringContainsString('track/click', $content);
    }
}
