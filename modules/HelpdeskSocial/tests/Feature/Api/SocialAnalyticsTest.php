<?php

namespace Modules\HelpdeskSocial\Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\HelpdeskSocial\Models\SocialAgentWorkload;
use Modules\HelpdeskSocial\Models\SocialComment;
use Modules\HelpdeskSocial\Models\SocialMention;
use Modules\HelpdeskSocial\Models\SocialMetrics;
use Modules\HelpdeskSocial\Models\SocialSlaPolicy;
use Modules\HelpdeskSocial\Tests\TestCase;

class SocialAnalyticsTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->user->givePermissionTo(['helpdesksocial.view', 'helpdesksocial.analytics.view']);
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

    public function test_can_get_agent_performance_with_active_workload(): void
    {
        $agent = User::factory()->create();
        SocialComment::factory()->count(2)->create([
            'assigned_to_user_id' => $agent->id,
            'replied_at' => now(),
        ]);
        SocialAgentWorkload::query()->create([
            'user_id' => $agent->id,
            'active_assigned_count' => 4,
        ]);

        $response = $this->getJson('/api/helpdesk/social/analytics/agent-performance');

        $response->assertOk()
            ->assertJsonCount(1, 'agents')
            ->assertJsonPath('agents.0.user_id', $agent->id)
            ->assertJsonPath('agents.0.comments_assigned', 2)
            ->assertJsonPath('agents.0.active_workload', 4);
    }

    public function test_can_get_sla_overview(): void
    {
        SocialSlaPolicy::factory()->create([
            'is_active' => true,
            'response_time_minutes' => 30,
        ]);
        SocialComment::factory()->count(2)->create([
            'posted_at' => now()->subMinutes(10),
            'replied_at' => now(),
        ]);

        $response = $this->getJson('/api/helpdesk/social/analytics/sla-overview');

        $response->assertOk()
            ->assertJsonPath('total_comments_with_reply', 2)
            ->assertJsonPath('default_sla_target_min', 30)
            ->assertJsonPath('sla_compliance_rate', 100.0);
    }

    public function test_can_get_sentiment_breakdown(): void
    {
        SocialMention::factory()->count(2)->create([
            'sentiment' => 'positive',
            'discovered_at' => now(),
        ]);
        SocialMention::factory()->create([
            'sentiment' => 'negative',
            'discovered_at' => now(),
        ]);

        $response = $this->getJson('/api/helpdesk/social/analytics/sentiment-breakdown');

        $response->assertOk()
            ->assertJsonPath('total_mentions', 3)
            ->assertJsonPath('breakdown.positive', 2)
            ->assertJsonPath('breakdown.negative', 1);
    }
}
