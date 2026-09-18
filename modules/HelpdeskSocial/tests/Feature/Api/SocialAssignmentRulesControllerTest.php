<?php

namespace Modules\HelpdeskSocial\Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\HelpdeskSocial\Models\SocialAssignmentRule;
use Modules\HelpdeskSocial\Tests\TestCase;

class SocialAssignmentRulesControllerTest extends TestCase
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
        $response = $this->getJson('/api/helpdesk/social/assignment-rules');

        $response->assertUnauthorized();
    }

    public function test_index_returns_data(): void
    {
        SocialAssignmentRule::factory()->count(3)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/helpdesk/social/assignment-rules');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.per_page', 20);
    }

    public function test_index_forbidden_without_permission(): void
    {
        $otherUser = User::factory()->create();
        $otherUser->givePermissionTo(['helpdesksocial.view']);
        $this->actingAs($otherUser, 'sanctum');

        $response = $this->getJson('/api/helpdesk/social/assignment-rules');

        $response->assertForbidden();
    }

    public function test_store_creates_resource(): void
    {
        $payload = [
            'name' => 'Assignment Rule',
            'conditions' => [
                ['field' => 'intent', 'operator' => 'equals', 'value' => 'complaint'],
            ],
            'assignment_strategy' => 'round_robin',
            'priority' => 10,
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/helpdesk/social/assignment-rules', $payload);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Assignment Rule');

        $this->assertDatabaseHas('helpdesk_social_assignment_rules', [
            'name' => 'Assignment Rule',
        ]);
    }

    public function test_store_validation_fails(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/helpdesk/social/assignment-rules', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'conditions']);
    }

    public function test_show_returns_resource(): void
    {
        $rule = SocialAssignmentRule::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/helpdesk/social/assignment-rules/{$rule->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $rule->id)
            ->assertJsonPath('data.name', $rule->name);
    }

    public function test_update_modifies_resource(): void
    {
        $rule = SocialAssignmentRule::factory()->create(['name' => 'Old Rule']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/helpdesk/social/assignment-rules/{$rule->id}", [
                'name' => 'Updated Rule',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Rule');

        $this->assertDatabaseHas('helpdesk_social_assignment_rules', [
            'id' => $rule->id,
            'name' => 'Updated Rule',
        ]);
    }

    public function test_destroy_deletes_resource(): void
    {
        $rule = SocialAssignmentRule::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/helpdesk/social/assignment-rules/{$rule->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'Regla de asignación eliminada correctamente');

        $this->assertDatabaseMissing('helpdesk_social_assignment_rules', ['id' => $rule->id]);
    }

    public function test_toggle_active(): void
    {
        $rule = SocialAssignmentRule::factory()->create(['is_active' => true]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/helpdesk/social/assignment-rules/{$rule->id}/toggle");

        $response->assertOk()
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('helpdesk_social_assignment_rules', [
            'id' => $rule->id,
            'is_active' => false,
        ]);
    }
}
