<?php

namespace Modules\Ecommerce\Tests\Feature\Api\V1\Auth;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Modules\Ecommerce\Models\Customer;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use DatabaseTransactions;

    public function test_logout_revokes_current_token(): void
    {
        $customer = Customer::factory()->create();
        $customer->createToken('first');
        $customer->createToken('second');
        Sanctum::actingAs($customer, ['*']);

        $response = $this->postJson('/api/v1/ecommerce/auth/logout');

        $response->assertOk()->assertJsonPath('success', true);
    }

    public function test_logout_all_revokes_all_tokens(): void
    {
        $customer = Customer::factory()->create();
        $customer->createToken('first');
        $customer->createToken('second');
        $customer->createToken('third');
        Sanctum::actingAs($customer, ['*']);

        $response = $this->postJson('/api/v1/ecommerce/auth/logout-all');

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertSame(0, $customer->tokens()->count());
    }

    public function test_logout_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/ecommerce/auth/logout');

        $response->assertUnauthorized()
            ->assertJsonPath('code', 'UNAUTHENTICATED');
    }
}
