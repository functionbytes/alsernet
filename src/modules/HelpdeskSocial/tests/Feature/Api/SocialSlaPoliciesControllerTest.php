<?php

namespace Modules\HelpdeskSocial\Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\HelpdeskSocial\Models\SocialSlaPolicy;
use Modules\HelpdeskSocial\Tests\TestCase;

class SocialSlaPoliciesControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedPermissions();

        $this->user = User::factory()->create();
        $this->user->givePermissionTo([
            'helpdesksocial.rules.manage',
        ]);
    }

    private function seedPermissions(): void {}

    public function test_index_requires_auth(): void
    {
        $response = $this->getJson('/api/helpdesk/social/sla-policies');

        $response->assertUnauthorized();
    }

    public function test_index_returns_data(): void
    {
        SocialSlaPolicy::factory()->count(3)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/helpdesk/social/sla-policies');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.per_page', 20);
    }

    public function test_index_forbidden_without_permission(): void
    {
        $otherUser = User::factory()->create();
        $otherUser->givePermissionTo(['helpdesksocial.view']);
        $this->actingAs($otherUser, 'sanctum');

        $response = $this->getJson('/api/helpdesk/social/sla-policies');

        $response->assertForbidden();
    }

    public function test_store_creates_resource(): void
    {
        $payload = [
            'name' => 'Urgent SLA',
            'response_time_minutes' => 30,
            'resolution_time_minutes' => 120,
            'platform' => 'facebook',
            'priority' => 'high',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/helpdesk/social/sla-policies', $payload);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Urgent SLA');

        $this->assertDatabaseHas('helpdesk_social_sla_policies', [
            'name' => 'Urgent SLA',
            'platform' => 'facebook',
        ]);
    }

    public function test_store_validation_fails(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/helpdesk/social/sla-policies', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'response_time_minutes']);
    }

    public function test_show_returns_resource(): void
    {
        $policy = SocialSlaPolicy::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/helpdesk/social/sla-policies/{$policy->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $policy->id)
            ->assertJsonPath('data.name', $policy->name);
    }

    public function test_update_modifies_resource(): void
    {
        $policy = SocialSlaPolicy::factory()->create(['name' => 'Old Policy']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/helpdesk/social/sla-policies/{$policy->id}", [
                'name' => 'Updated Policy',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Policy');

        $this->assertDatabaseHas('helpdesk_social_sla_policies', [
            'id' => $policy->id,
            'name' => 'Updated Policy',
        ]);
    }

    public function test_destroy_deletes_resource(): void
    {
        $policy = SocialSlaPolicy::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/helpdesk/social/sla-policies/{$policy->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'Política SLA eliminada correctamente');

        $this->assertDatabaseMissing('helpdesk_social_sla_policies', ['id' => $policy->id]);
    }
}
