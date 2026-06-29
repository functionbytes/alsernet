<?php

namespace Modules\Remarketing\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Remarketing\Models\Store;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SenderWizardTest extends TestCase
{
    use DatabaseTransactions;

    private User $owner;

    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('remarketing_stores')) {
            $this->markTestSkipped('Migraciones del módulo Remarketing no aplicadas.');
        }

        $this->owner = $this->userWithPermissions(['remarketing.stores.view', 'remarketing.stores.create', 'remarketing.manage']);
        $this->store = $this->createStore($this->owner);
    }

    public function test_health_page_requires_auth(): void
    {
        $this->get(route('remarketing.stores.health', $this->store))
            ->assertRedirect();
    }

    public function test_health_page_is_accessible_to_store_owner(): void
    {
        $this->actingAs($this->owner)
            ->get(route('remarketing.stores.health', $this->store))
            ->assertOk()
            ->assertViewIs('remarketing::stores.health')
            ->assertViewHas('store')
            ->assertViewHas('check');
    }

    public function test_health_page_returns_check_with_expected_keys(): void
    {
        $response = $this->actingAs($this->owner)
            ->get(route('remarketing.stores.health', $this->store));

        $check = $response->viewData('check');

        $this->assertArrayHasKey('spf', $check);
        $this->assertArrayHasKey('dkim', $check);
        $this->assertArrayHasKey('dmarc', $check);
        $this->assertArrayHasKey('details', $check);
    }

    public function test_health_page_forbidden_for_other_user(): void
    {
        $other = $this->userWithPermissions(['remarketing.stores.view']);

        $this->actingAs($other)
            ->get(route('remarketing.stores.health', $this->store))
            ->assertForbidden();
    }

    public function test_dns_check_endpoint_returns_json_with_correct_keys(): void
    {
        $response = $this->actingAs($this->owner)
            ->get(route('remarketing.stores.dns-check', $this->store));

        $response->assertOk()
            ->assertJsonStructure(['domain', 'spf', 'dkim', 'dmarc', 'details']);
    }

    public function test_verify_returns_success_for_resolvable_domain(): void
    {
        $this->actingAs($this->owner)
            ->post(route('remarketing.stores.verify'), [
                'platform' => 'shopify',
                'name' => 'My Shop',
                'domain' => 'https://google.com',
            ])
            ->assertOk()
            ->assertJsonFragment(['message' => 'Conexión verificada.']);
    }

    public function test_verify_returns_422_for_unresolvable_domain(): void
    {
        $this->actingAs($this->owner)
            ->post(route('remarketing.stores.verify'), [
                'platform' => 'shopify',
                'name' => 'My Shop',
                'domain' => 'https://this-domain-totally-does-not-exist-xyz123456789.invalid',
            ])
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'No se pudo resolver el DNS del dominio.']);
    }

    private function userWithPermissions(array $permissions): User
    {
        $user = User::factory()->create();

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $user->givePermissionTo($permissions);

        return $user;
    }

    private function createStore(User $user): Store
    {
        return Store::query()->create([
            'user_id' => $user->id,
            'platform' => 'manual',
            'name' => 'Test Store '.uniqid(),
            'domain' => 'https://test-'.uniqid().'.example.com',
            'status' => 'active',
            'webhook_token' => Str::random(64),
        ]);
    }
}
