<?php

namespace Modules\HelpdeskSocial\Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\HelpdeskSocial\Models\SocialAccount;
use Modules\HelpdeskSocial\Models\SocialCompetitor;
use Modules\HelpdeskSocial\Tests\TestCase;

class SocialCompetitorsControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedPermissions();

        $this->user = User::factory()->create();
        $this->user->givePermissionTo([
            'helpdesksocial.view-analytics',
        ]);
    }

    private function seedPermissions(): void {}

    public function test_index_requires_auth(): void
    {
        $response = $this->getJson('/api/helpdesk/social/competitors');

        $response->assertUnauthorized();
    }

    public function test_index_returns_data(): void
    {
        SocialCompetitor::factory()->count(3)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/helpdesk/social/competitors');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.per_page', 20);
    }

    public function test_index_forbidden_without_permission(): void
    {
        $otherUser = User::factory()->create();
        $otherUser->givePermissionTo(['helpdesksocial.view']);
        $this->actingAs($otherUser, 'sanctum');

        $response = $this->getJson('/api/helpdesk/social/competitors');

        $response->assertForbidden();
    }

    public function test_store_creates_resource(): void
    {
        $account = SocialAccount::factory()->create();

        $payload = [
            'social_account_id' => $account->id,
            'name' => 'Competitor A',
            'platform' => 'facebook',
            'external_id' => 'comp_123',
            'username' => 'competitor_a',
            'profile_url' => 'https://facebook.com/competitor_a',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/helpdesk/social/competitors', $payload);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Competitor A');

        $this->assertDatabaseHas('helpdesk_social_competitors', [
            'external_id' => 'comp_123',
            'name' => 'Competitor A',
        ]);
    }

    public function test_store_validation_fails(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/helpdesk/social/competitors', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['social_account_id', 'name', 'platform', 'external_id']);
    }

    public function test_show_returns_resource(): void
    {
        $competitor = SocialCompetitor::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/helpdesk/social/competitors/{$competitor->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $competitor->id)
            ->assertJsonPath('data.name', $competitor->name);
    }

    public function test_update_modifies_resource(): void
    {
        $competitor = SocialCompetitor::factory()->create(['name' => 'Old Name']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/helpdesk/social/competitors/{$competitor->id}", [
                'name' => 'New Name',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'New Name');

        $this->assertDatabaseHas('helpdesk_social_competitors', [
            'id' => $competitor->id,
            'name' => 'New Name',
        ]);
    }

    public function test_destroy_deletes_resource(): void
    {
        $competitor = SocialCompetitor::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/helpdesk/social/competitors/{$competitor->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'Competidor eliminado correctamente');

        $this->assertDatabaseMissing('helpdesk_social_competitors', ['id' => $competitor->id]);
    }
}
