<?php

namespace Modules\HelpdeskSocial\Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\HelpdeskSocial\Models\SocialComment;
use Modules\HelpdeskSocial\Models\SocialMetrics;
use Modules\HelpdeskSocial\Tests\TestCase;

class SocialAnalyticsTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->user->givePermissionTo(['helpdesksocial.view', 'helpdesksocial.view-analytics']);
        $this->actingAs($this->user, 'sanctum');
    }

    public function test_can_get_analytics_overview(): void
    {
        SocialComment::factory()->count(5)->create([
            'platform' => 'facebook',
            'posted_at' => now()->subDays(5),
        ]);
        SocialComment::factory()->count(3)->create([
            'platform' => 'instagram',
            'posted_at' => now()->subDays(3),
            'replied_at' => now(),
            'auto_replied' => true,
        ]);

        $response = $this->getJson('/api/helpdesk/social/analytics/overview');

        $response->assertOk()
            ->assertJsonPath('summary.total_comments', 8)
            ->assertJsonPath('summary.total_replies', 3)
            ->assertJsonPath('by_platform.0.count', 5)
            ->assertJsonPath('by_platform.1.count', 3);
    }

    public function test_can_get_analytics_metrics(): void
    {
        SocialMetrics::factory()->count(5)->create();

        $response = $this->getJson('/api/helpdesk/social/analytics/metrics');

        $response->assertOk()
            ->assertJsonCount(5, 'metrics');
    }

    public function test_can_get_agents_performance(): void
    {
        $agent = User::factory()->create();
        SocialComment::factory()->count(3)->create([
            'assigned_to_user_id' => $agent->id,
            'replied_at' => now(),
        ]);

        $response = $this->getJson('/api/helpdesk/social/analytics/agents');

        $response->assertOk()
            ->assertJsonCount(1, 'agents')
            ->assertJsonPath('agents.0.comments_assigned', 3);
    }
}
