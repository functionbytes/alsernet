<?php

namespace Modules\Mailrelay\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Mailrelay\Entities\Campaign;
use Modules\Mailrelay\Entities\MailProvider;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class MailProviderApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected User $unauthorizedUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions
        Permission::create(['name' => 'mailrelay.providers.view', 'guard_name' => 'web']);
        Permission::create(['name' => 'mailrelay.providers.create', 'guard_name' => 'web']);
        Permission::create(['name' => 'mailrelay.providers.edit', 'guard_name' => 'web']);
        Permission::create(['name' => 'mailrelay.providers.delete', 'guard_name' => 'web']);

        // Create authorized user
        $this->user = User::factory()->create();
        $this->user->givePermissionTo([
            'mailrelay.providers.view',
            'mailrelay.providers.create',
            'mailrelay.providers.edit',
            'mailrelay.providers.delete',
        ]);

        // Create unauthorized user
        $this->unauthorizedUser = User::factory()->create();
    }

    public function test_can_list_providers(): void
    {
        MailProvider::factory()->count(3)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/providers');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'driver',
                        'is_active',
                        'is_default',
                        'priority',
                    ],
                ],
                'meta',
            ]);
    }

    public function test_unauthorized_user_cannot_list_providers(): void
    {
        $response = $this->actingAs($this->unauthorizedUser, 'sanctum')
            ->getJson('/api/v1/providers');

        $response->assertStatus(403);
    }

    public function test_can_create_provider(): void
    {
        $providerData = [
            'name' => 'Test SendGrid',
            'driver' => 'sendgrid',
            'credentials' => [
                'api_key' => 'test_key_123',
                'from_email' => 'test@example.com',
                'from_name' => 'Test Sender',
                'reply_to' => 'reply@example.com',
            ],
            'is_active' => true,
            'is_default' => false,
            'priority' => 50,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/providers', $providerData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'driver',
                ],
            ]);

        $this->assertDatabaseHas('mail_providers', [
            'name' => 'Test SendGrid',
            'driver' => 'sendgrid',
        ]);
    }

    public function test_can_show_provider(): void
    {
        $provider = MailProvider::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/providers/{$provider->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $provider->id,
                    'name' => $provider->name,
                ],
            ]);
    }

    public function test_can_update_provider(): void
    {
        $provider = MailProvider::factory()->create(['name' => 'Old Name']);

        $updateData = [
            'name' => 'New Name',
            'credentials' => [
                'api_key' => 'updated_key',
                'from_email' => 'new@example.com',
                'from_name' => 'New Sender',
            ],
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/providers/{$provider->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Mail provider updated successfully',
            ]);

        $this->assertDatabaseHas('mail_providers', [
            'id' => $provider->id,
            'name' => 'New Name',
        ]);
    }

    public function test_cannot_delete_default_provider(): void
    {
        $provider = MailProvider::factory()->create([
            'is_default' => true,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/providers/{$provider->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('mail_providers', [
            'id' => $provider->id,
        ]);
    }

    public function test_cannot_delete_provider_with_campaigns(): void
    {
        $provider = MailProvider::factory()->create(['is_default' => false]);
        Campaign::factory()->create(['mail_provider_id' => $provider->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/providers/{$provider->id}");

        $response->assertStatus(403);
    }

    public function test_can_delete_provider_without_campaigns(): void
    {
        $provider = MailProvider::factory()->create(['is_default' => false]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/providers/{$provider->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Mail provider deleted successfully',
            ]);

        $this->assertDatabaseMissing('mail_providers', [
            'id' => $provider->id,
        ]);
    }

    public function test_can_set_provider_as_default(): void
    {
        $oldDefault = MailProvider::factory()->create([
            'is_default' => true,
            'is_active' => true,
        ]);

        $newDefault = MailProvider::factory()->create([
            'is_default' => false,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/providers/{$newDefault->id}/set-default");

        $response->assertStatus(200);

        $this->assertDatabaseHas('mail_providers', [
            'id' => $newDefault->id,
            'is_default' => true,
        ]);

        $this->assertDatabaseHas('mail_providers', [
            'id' => $oldDefault->id,
            'is_default' => false,
        ]);
    }

    public function test_can_toggle_provider_active_status(): void
    {
        $provider = MailProvider::factory()->create([
            'is_active' => true,
            'is_default' => false,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/providers/{$provider->id}/toggle-active");

        $response->assertStatus(200);

        $this->assertDatabaseHas('mail_providers', [
            'id' => $provider->id,
            'is_active' => false,
        ]);
    }

    public function test_cannot_deactivate_default_provider(): void
    {
        $provider = MailProvider::factory()->create([
            'is_default' => true,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/providers/{$provider->id}/toggle-active");

        $response->assertStatus(403);

        $this->assertDatabaseHas('mail_providers', [
            'id' => $provider->id,
            'is_active' => true,
        ]);
    }

    public function test_can_list_available_drivers(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/drivers');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'driver',
                        'info',
                    ],
                ],
            ]);
    }
}
