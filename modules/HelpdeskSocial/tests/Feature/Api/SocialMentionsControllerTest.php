<?php

namespace Modules\HelpdeskSocial\Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\HelpdeskSocial\Models\SocialMention;
use Modules\HelpdeskSocial\Tests\TestCase;

class SocialMentionsControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedPermissions();

        $this->user = User::factory()->create();
        $this->user->givePermissionTo([
            'helpdesksocial.view',
        ]);
    }

    private function seedPermissions(): void {}

    public function test_index_requires_auth(): void
    {
        $response = $this->getJson('/api/helpdesk/social/mentions');

        $response->assertUnauthorized();
    }

    public function test_index_returns_data(): void
    {
        SocialMention::factory()->count(3)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/helpdesk/social/mentions');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.per_page', 25);
    }

    public function test_index_forbidden_without_permission(): void
    {
        $otherUser = User::factory()->create();
        $this->actingAs($otherUser, 'sanctum');

        $response = $this->getJson('/api/helpdesk/social/mentions');

        $response->assertForbidden();
    }

    public function test_index_filters_by_platform(): void
    {
        SocialMention::factory()->count(2)->create(['platform' => 'facebook']);
        SocialMention::factory()->count(1)->create(['platform' => 'instagram']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/helpdesk/social/mentions?platform=facebook');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_show_returns_resource(): void
    {
        $mention = SocialMention::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/helpdesk/social/mentions/{$mention->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $mention->id);
    }

    public function test_update_modifies_resource(): void
    {
        $mention = SocialMention::factory()->create(['status' => 'new']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/helpdesk/social/mentions/{$mention->id}", [
                'status' => 'reviewed',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'reviewed');

        $this->assertDatabaseHas('helpdesk_social_mentions', [
            'id' => $mention->id,
            'status' => 'reviewed',
        ]);
    }

    public function test_update_validation_fails(): void
    {
        $mention = SocialMention::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/helpdesk/social/mentions/{$mention->id}", [
                'status' => 'invalid-status',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    }

    public function test_destroy_deletes_resource(): void
    {
        $mention = SocialMention::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/helpdesk/social/mentions/{$mention->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'Mención eliminada correctamente');

        $this->assertDatabaseMissing('helpdesk_social_mentions', ['id' => $mention->id]);
    }
}
