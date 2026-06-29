<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Ecommerce\Enums\CustomerStatus;
use Modules\Ecommerce\Models\Customer;
use Tests\TestCase;

class MeTest extends TestCase
{
    use RefreshDatabase;

    public function test_me_returns_customer_payload(): void
    {
        $customer = Customer::factory()->create([
            'name' => 'Maria',
            'email' => 'maria@example.com',
            'email_verified_at' => now(),
            'status' => CustomerStatus::ACTIVE,
        ]);
        Sanctum::actingAs($customer, ['*']);

        $response = $this->getJson('/api/v1/me');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => ['id', 'name', 'email', 'emailVerifiedAt', 'avatarUrl', 'status'],
                    'abilities',
                    'modules',
                    'settings' => ['currency', 'locale', 'country'],
                ],
            ])
            ->assertJsonPath('data.user.email', 'maria@example.com')
            ->assertJsonPath('data.user.status', 'active');

        $abilities = $response->json('data.abilities');
        $this->assertContains('orders.create', $abilities);
        $this->assertContains('addresses.manage', $abilities);
    }

    public function test_me_unauthenticated_returns_401(): void
    {
        $response = $this->getJson('/api/v1/me');

        $response->assertUnauthorized()
            ->assertJsonPath('code', 'UNAUTHENTICATED');
    }

    public function test_me_rejects_admin_token_with_audience_mismatch(): void
    {
        $admin = User::factory()->create();
        Sanctum::actingAs($admin, ['*']);

        $response = $this->getJson('/api/v1/me');

        $response->assertForbidden()
            ->assertJsonPath('code', 'AUDIENCE_MISMATCH');
    }

    public function test_me_modules_returns_ecommerce_manifest(): void
    {
        $customer = Customer::factory()->create();
        Sanctum::actingAs($customer, ['*']);

        $response = $this->getJson('/api/v1/me/modules');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $modules = $response->json('data');
        $aliases = array_column($modules, 'alias');
        $this->assertContains('ecommerce', $aliases);
    }

    public function test_me_update_changes_profile_fields(): void
    {
        $customer = Customer::factory()->create(['name' => 'Old Name', 'phone' => null]);
        Sanctum::actingAs($customer, ['*']);

        $response = $this->putJson('/api/v1/me', [
            'name' => 'New Name',
            'phone' => '555-9999',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.user.name', 'New Name')
            ->assertJsonPath('data.user.phone', '555-9999');
    }
}
