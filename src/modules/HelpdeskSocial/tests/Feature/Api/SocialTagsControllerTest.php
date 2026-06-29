<?php

namespace Modules\HelpdeskSocial\Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\HelpdeskSocial\Models\SocialTag;
use Modules\HelpdeskSocial\Tests\TestCase;
use Spatie\Permission\PermissionRegistrar;

class SocialTagsControllerTest extends TestCase
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
            'helpdesksocial.rules.manage',
        ]);
    }

    private function seedPermissions(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_index_requires_auth(): void
    {
        $response = $this->getJson('/api/helpdesk/social/tags');

        $response->assertUnauthorized();
    }

    public function test_index_returns_data(): void
    {
        SocialTag::factory()->count(3)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/helpdesk/social/tags');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.per_page', 20);
    }

    public function test_index_forbidden_without_permission(): void
    {
        $otherUser = User::factory()->create();
        $this->actingAs($otherUser, 'sanctum');

        $response = $this->getJson('/api/helpdesk/social/tags');

        $response->assertForbidden();
    }

    public function test_store_creates_resource(): void
    {
        $payload = [
            'name' => 'Test Tag',
            'slug' => 'test-tag',
            'color' => '#90bb13',
            'description' => 'A test tag',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/helpdesk/social/tags', $payload);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Test Tag');

        $this->assertDatabaseHas('helpdesk_social_tags', [
            'slug' => 'test-tag',
            'name' => 'Test Tag',
        ]);
    }

    public function test_store_validation_fails(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/helpdesk/social/tags', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'slug']);
    }

    public function test_show_returns_resource(): void
    {
        $tag = SocialTag::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/helpdesk/social/tags/{$tag->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $tag->id)
            ->assertJsonPath('data.name', $tag->name);
    }

    public function test_update_modifies_resource(): void
    {
        $tag = SocialTag::factory()->create(['name' => 'Old Name']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/helpdesk/social/tags/{$tag->id}", [
                'name' => 'New Name',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'New Name');

        $this->assertDatabaseHas('helpdesk_social_tags', [
            'id' => $tag->id,
            'name' => 'New Name',
        ]);
    }

    public function test_destroy_deletes_resource(): void
    {
        $tag = SocialTag::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/helpdesk/social/tags/{$tag->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'Etiqueta eliminada correctamente');

        $this->assertDatabaseMissing('helpdesk_social_tags', ['id' => $tag->id]);
    }
}
