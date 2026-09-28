<?php

namespace Modules\Erp\Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Erp\Models\ErpEndpoint;
use Modules\Erp\Models\ErpEndpointLog;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ErpEndpointsApiTest extends TestCase
{
    use DatabaseTransactions;

    // mysql/mariadb/helpdesk apuntan a la MISMA BD real - RefreshDatabase la
    // migro-fresh por un fallo de force="true" en phpunit.xml (incidente
    // 29-ago-2026) - nunca usar RefreshDatabase en este proyecto.
    protected array $connectionsToTransact = ['mysql', 'mariadb', 'helpdesk'];

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();

        // Las rutas de gestión exigen 'erp.endpoints.manage' (ver routes/api.php).
        Permission::firstOrCreate(['name' => 'erp.endpoints.manage', 'guard_name' => 'web']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->user->givePermissionTo('erp.endpoints.manage');
    }

    public function test_can_list_endpoints(): void
    {
        ErpEndpoint::factory(3)->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/erp/v2/endpoints');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'url', 'method', 'is_active'],
            ],
        ]);
    }

    public function test_can_create_endpoint(): void
    {
        $data = [
            'name' => 'Test Endpoint',
            'url' => 'https://example.com/users',
            'method' => 'GET',
            'description' => 'Test endpoint for users',
            'timeout' => 30,
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/erp/v2/endpoints', $data);

        $response->assertCreated();
        $response->assertJsonStructure([
            'message',
            'data' => ['id', 'name', 'url', 'method'],
        ]);

        $this->assertDatabaseHas('erp_endpoints', [
            'name' => 'Test Endpoint',
            'url' => $data['url'],
        ]);
    }

    public function test_endpoint_creation_requires_valid_url(): void
    {
        $data = [
            'name' => 'Invalid Endpoint',
            'url' => 'not a url',
            'method' => 'GET',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/erp/v2/endpoints', $data);

        $response->assertUnprocessable();
        $response->assertJsonStructure(['errors' => ['url']]);
    }

    public function test_can_show_endpoint(): void
    {
        $endpoint = ErpEndpoint::factory()->create();

        $response = $this->actingAs($this->user)
            ->getJson("/api/erp/v2/endpoints/{$endpoint->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => ['id', 'name', 'url', 'method'],
            'statistics' => ['success_rate', 'average_execution_time'],
        ]);
    }

    public function test_can_update_endpoint(): void
    {
        $endpoint = ErpEndpoint::factory()->create();

        $data = [
            'name' => 'Updated Endpoint',
            'url' => 'https://example.org/endpoint',
            'method' => 'POST',
            'is_active' => false,
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/erp/v2/endpoints/{$endpoint->id}", $data);

        $response->assertOk();
        $this->assertDatabaseHas('erp_endpoints', [
            'id' => $endpoint->id,
            'name' => 'Updated Endpoint',
        ]);
    }

    public function test_can_delete_endpoint(): void
    {
        $endpoint = ErpEndpoint::factory()->create();

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/erp/v2/endpoints/{$endpoint->id}");

        $response->assertOk();
        $this->assertSoftDeleted($endpoint);
    }

    public function test_can_toggle_endpoint_active_status(): void
    {
        $endpoint = ErpEndpoint::factory()->create(['is_active' => false]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/erp/v2/endpoints/{$endpoint->id}/toggle");

        $response->assertOk();
        $this->assertTrue($endpoint->refresh()->is_active);
    }

    public function test_can_test_endpoint_connection(): void
    {
        $endpoint = ErpEndpoint::factory()->create([
            'url' => 'https://jsonplaceholder.typicode.com/users/1',
            'method' => 'GET',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/erp/v2/endpoints/{$endpoint->id}/test");

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'status_code',
            'execution_time',
        ]);
    }

    public function test_can_get_endpoint_logs(): void
    {
        $endpoint = ErpEndpoint::factory()->create();
        ErpEndpointLog::factory(5)->create(['endpoint_id' => $endpoint->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/erp/v2/endpoints/{$endpoint->id}/logs");

        $response->assertOk();
        $response->assertJsonStructure([
            'endpoint' => ['id', 'name'],
            'data' => [
                '*' => ['id', 'status_code', 'execution_time', 'success'],
            ],
        ]);
    }

    public function test_can_clear_endpoint_logs(): void
    {
        $endpoint = ErpEndpoint::factory()->create();
        ErpEndpointLog::factory(3)->create(['endpoint_id' => $endpoint->id]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/erp/v2/endpoints/{$endpoint->id}/logs");

        $response->assertOk();
        $this->assertEquals(0, $endpoint->logs()->count());
    }

    public function test_can_get_endpoint_statistics(): void
    {
        $endpoint = ErpEndpoint::factory()->create();
        ErpEndpointLog::factory(5)->create(['endpoint_id' => $endpoint->id, 'success' => true]);
        ErpEndpointLog::factory(2)->create(['endpoint_id' => $endpoint->id, 'success' => false]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/erp/v2/endpoints/{$endpoint->id}/statistics");

        $response->assertOk();
        $response->assertJsonStructure([
            'endpoint' => ['id', 'name'],
            'statistics' => [
                'total_calls',
                'success_rate',
                'average_execution_time',
                'success_count',
                'failed_count',
            ],
        ]);
    }

    public function test_requires_authentication(): void
    {
        $endpoint = ErpEndpoint::factory()->create();

        $response = $this->getJson("/api/erp/v2/endpoints/{$endpoint->id}");

        $response->assertUnauthorized();
    }

    public function test_unversioned_prefix_is_marked_deprecated(): void
    {
        $this->actingAs($this->user)
            ->getJson('/api/erp/endpoints')
            ->assertOk()
            ->assertHeader('Deprecation', 'true')
            ->assertHeader('Link', '</api/erp/v2/endpoints>; rel="successor-version"');

        $this->actingAs($this->user)
            ->getJson('/api/erp/v2/endpoints')
            ->assertOk()
            ->assertHeaderMissing('Deprecation');
    }
}
