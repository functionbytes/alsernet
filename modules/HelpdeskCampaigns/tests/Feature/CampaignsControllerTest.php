<?php

namespace Modules\HelpdeskCampaigns\Tests\Feature;

use Illuminate\Contracts\Auth\Access\Gate;
use Modules\HelpdeskCampaigns\Models\Campaign;
use Tests\TestCase;

class CampaignsControllerTest extends TestCase
{
    public function test_campaign_model_resolves(): void
    {
        $this->assertTrue(class_exists(Campaign::class));
    }

    public function test_campaign_controller_route_registered(): void
    {
        $routes = collect(app('router')->getRoutes())
            ->filter(fn ($r) => $r->getName() === 'manager.helpdesk-campaigns.index');

        $this->assertTrue($routes->isNotEmpty(), 'Campaign index route should be registered');
    }

    public function test_policy_registered_on_campaign_model(): void
    {
        $policy = app(Gate::class)->getPolicyFor(Campaign::class);

        $this->assertNotNull($policy);
    }
}
