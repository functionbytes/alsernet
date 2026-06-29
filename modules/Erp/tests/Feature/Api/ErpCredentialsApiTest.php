<?php

namespace Modules\Erp\Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Erp\Models\ErpCredential;
use Modules\Erp\Models\ErpEndpoint;
use Tests\TestCase;

class ErpCredentialsApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected ErpEndpoint $endpoint;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->endpoint = ErpEndpoint::factory()->create();
    }

    public function test_can_list_credentials_for_endpoint(): void
    {
        $this->endpoint->credentials()->createMany([
            ['auth_type' => 'basic', 'username' => 'user1', 'password' => 'pass1'],
            ['auth_type' => 'bearer', 'token' => 'token123'],
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/erp/v2/endpoints/{$this->endpoint->id}/credentials");

        $response->assertOk();
        $response->assertJsonStructure([
            'endpoint' => ['id', 'name'],
            'data' => [
                '*' => ['id', 'auth_type', 'is_active'],
            ],
        ]);
    }

    public function test_can_create_basic_auth_credential(): void
    {
        $data = [
            'auth_type' => 'basic',
            'username' => 'admin',
            'password' => 'secretpassword',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/erp/v2/endpoints/{$this->endpoint->id}/credentials", $data);

        $response->assertCreated();
        $response->assertJsonStructure([
            'message',
            'data' => ['id', 'auth_type', 'username'],
        ]);

        $this->assertDatabaseHas('erp_credentials', [
            'endpoint_id' => $this->endpoint->id,
            'auth_type' => 'basic',
        ]);
    }

    public function test_can_create_bearer_token_credential(): void
    {
        $data = [
            'auth_type' => 'bearer',
            'token' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/erp/v2/endpoints/{$this->endpoint->id}/credentials", $data);

        $response->assertCreated();
        $this->assertDatabaseHas('erp_credentials', [
            'endpoint_id' => $this->endpoint->id,
            'auth_type' => 'bearer',
        ]);
    }

    public function test_can_create_api_key_credential(): void
    {
        $data = [
            'auth_type' => 'api_key',
            'api_key' => 'sk_test_51234567890',
            'api_key_header' => 'X-API-Key',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/erp/v2/endpoints/{$this->endpoint->id}/credentials", $data);

        $response->assertCreated();
        $this->assertDatabaseHas('erp_credentials', [
            'endpoint_id' => $this->endpoint->id,
            'auth_type' => 'api_key',
        ]);
    }

    public function test_can_create_no_auth_credential(): void
    {
        $data = [
            'auth_type' => 'none',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/erp/v2/endpoints/{$this->endpoint->id}/credentials", $data);

        $response->assertCreated();
    }

    public function test_basic_auth_requires_username_and_password(): void
    {
        $data = [
            'auth_type' => 'basic',
            // Missing username and password
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/erp/v2/endpoints/{$this->endpoint->id}/credentials", $data);

        $response->assertUnprocessable();
    }

    public function test_bearer_auth_requires_token(): void
    {
        $data = [
            'auth_type' => 'bearer',
            // Missing token
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/erp/v2/endpoints/{$this->endpoint->id}/credentials", $data);

        $response->assertUnprocessable();
    }

    public function test_can_show_credential(): void
    {
        $credential = $this->endpoint->credentials()->create([
            'auth_type' => 'basic',
            'username' => 'user',
            'password' => 'pass',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/erp/v2/endpoints/{$this->endpoint->id}/credentials/{$credential->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => ['id', 'auth_type', 'is_active'],
        ]);
    }

    public function test_can_update_credential(): void
    {
        $credential = $this->endpoint->credentials()->create([
            'auth_type' => 'basic',
            'username' => 'user',
            'password' => 'pass',
        ]);

        $data = [
            'auth_type' => 'bearer',
            'token' => 'newtoken123',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/erp/v2/endpoints/{$this->endpoint->id}/credentials/{$credential->id}", $data);

        $response->assertOk();
        $this->assertDatabaseHas('erp_credentials', [
            'id' => $credential->id,
            'auth_type' => 'bearer',
        ]);
    }

    public function test_can_delete_credential(): void
    {
        $credential = $this->endpoint->credentials()->create([
            'auth_type' => 'basic',
            'username' => 'user',
            'password' => 'pass',
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/erp/v2/endpoints/{$this->endpoint->id}/credentials/{$credential->id}");

        $response->assertOk();
        $this->assertSoftDeleted($credential);
    }

    public function test_can_toggle_credential_active_status(): void
    {
        $credential = $this->endpoint->credentials()->create([
            'auth_type' => 'basic',
            'username' => 'user',
            'password' => 'pass',
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/erp/v2/endpoints/{$this->endpoint->id}/credentials/{$credential->id}/toggle");

        $response->assertOk();
        $this->assertTrue($credential->refresh()->is_active);
    }

    public function test_activating_credential_deactivates_others(): void
    {
        $cred1 = $this->endpoint->credentials()->create([
            'auth_type' => 'basic',
            'username' => 'user1',
            'password' => 'pass1',
            'is_active' => true,
        ]);

        $cred2 = $this->endpoint->credentials()->create([
            'auth_type' => 'bearer',
            'token' => 'token2',
            'is_active' => false,
        ]);

        $this->actingAs($this->user)
            ->postJson("/api/erp/v2/endpoints/{$this->endpoint->id}/credentials/{$cred2->id}/toggle");

        $this->assertFalse($cred1->refresh()->is_active);
        $this->assertTrue($cred2->refresh()->is_active);
    }

    public function test_can_rotate_credential(): void
    {
        $credential = $this->endpoint->credentials()->create([
            'auth_type' => 'basic',
            'username' => 'user',
            'password' => 'pass',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/erp/v2/endpoints/{$this->endpoint->id}/credentials/{$credential->id}/rotate");

        $response->assertCreated();
        $response->assertJsonStructure([
            'message',
            'data' => ['id', 'auth_type'],
        ]);

        // New credential should be created but inactive
        $newCredential = ErpCredential::where('endpoint_id', $this->endpoint->id)
            ->where('id', '!=', $credential->id)
            ->first();

        $this->assertNotNull($newCredential);
        $this->assertFalse($newCredential->is_active);
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson("/api/erp/v2/endpoints/{$this->endpoint->id}/credentials");

        $response->assertUnauthorized();
    }
}
