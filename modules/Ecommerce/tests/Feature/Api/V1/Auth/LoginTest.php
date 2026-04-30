<?php

namespace Modules\Ecommerce\Tests\Feature\Api\V1\Auth;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Modules\Ecommerce\Enums\CustomerStatus;
use Modules\Ecommerce\Models\Customer;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use DatabaseTransactions;

    public function test_customer_can_login_with_valid_credentials(): void
    {
        $customer = Customer::factory()->create([
            'email' => 'juan@example.com',
            'password' => 'secret-password',
            'status' => CustomerStatus::ACTIVE,
        ]);

        $response = $this->withoutMiddleware([ThrottleRequests::class])
            ->postJson('/api/v1/ecommerce/auth/login', [
                'email' => 'juan@example.com',
                'password' => 'secret-password',
                'device_name' => 'iPhone 15',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['customer' => ['id'], 'token', 'tokenType']]);

        $this->assertNotEmpty($customer->fresh()->tokens);
    }

    public function test_login_with_invalid_credentials_returns_401(): void
    {
        Customer::factory()->create([
            'email' => 'juan@example.com',
            'password' => 'secret-password',
            'status' => CustomerStatus::ACTIVE,
        ]);

        $response = $this->withoutMiddleware([ThrottleRequests::class])
            ->postJson('/api/v1/ecommerce/auth/login', [
                'email' => 'juan@example.com',
                'password' => 'wrong-password',
            ]);

        $response->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'INVALID_CREDENTIALS');
    }

    public function test_login_with_unknown_email_returns_401(): void
    {
        $response = $this->withoutMiddleware([ThrottleRequests::class])
            ->postJson('/api/v1/ecommerce/auth/login', [
                'email' => 'nope@example.com',
                'password' => 'whatever',
            ]);

        $response->assertUnauthorized()
            ->assertJsonPath('code', 'INVALID_CREDENTIALS');
    }

    public function test_login_blocks_inactive_account(): void
    {
        Customer::factory()->create([
            'email' => 'inactive@example.com',
            'password' => 'secret-password',
            'status' => CustomerStatus::INACTIVE,
        ]);

        $response = $this->withoutMiddleware([ThrottleRequests::class])
            ->postJson('/api/v1/ecommerce/auth/login', [
                'email' => 'inactive@example.com',
                'password' => 'secret-password',
            ]);

        $response->assertForbidden()
            ->assertJsonPath('code', 'ACCOUNT_INACTIVE');
    }

    public function test_login_validation_errors(): void
    {
        $response = $this->withoutMiddleware([ThrottleRequests::class])
            ->postJson('/api/v1/ecommerce/auth/login', []);

        $response->assertUnprocessable()
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonValidationErrors(['email', 'password']);
    }
}
