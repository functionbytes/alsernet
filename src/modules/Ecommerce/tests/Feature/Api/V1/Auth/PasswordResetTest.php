<?php

namespace Modules\Ecommerce\Tests\Feature\Api\V1\Auth;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Modules\Ecommerce\Models\Customer;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use DatabaseTransactions;

    public function test_forgot_password_sends_reset_link(): void
    {
        Notification::fake();
        $customer = Customer::factory()->create(['email' => 'reset@example.com']);

        $response = $this->withoutMiddleware([ThrottleRequests::class])
            ->postJson('/api/v1/ecommerce/auth/forgot-password', [
                'email' => 'reset@example.com',
            ]);

        $response->assertOk()->assertJsonPath('success', true);
        Notification::assertSentTo($customer, ResetPassword::class);
    }

    public function test_forgot_password_does_not_leak_unknown_email(): void
    {
        $response = $this->withoutMiddleware([ThrottleRequests::class])
            ->postJson('/api/v1/ecommerce/auth/forgot-password', [
                'email' => 'unknown@example.com',
            ]);

        $response->assertOk()->assertJsonPath('success', true);
    }

    public function test_reset_password_with_valid_token_changes_password(): void
    {
        $customer = Customer::factory()->create([
            'email' => 'reset@example.com',
            'password' => 'old-password',
        ]);
        $customer->createToken('mobile');
        $token = Password::broker('ecommerce_customers')->createToken($customer);

        $response = $this->withoutMiddleware([ThrottleRequests::class])
            ->postJson('/api/v1/ecommerce/auth/reset-password', [
                'token' => $token,
                'email' => 'reset@example.com',
                'password' => 'brand-new-password',
                'password_confirmation' => 'brand-new-password',
            ]);

        $response->assertOk();
        $this->assertTrue(Hash::check('brand-new-password', $customer->fresh()->password));
        $this->assertSame(0, $customer->fresh()->tokens()->count());
    }

    public function test_reset_password_with_invalid_token_fails(): void
    {
        Customer::factory()->create(['email' => 'reset@example.com']);

        $response = $this->withoutMiddleware([ThrottleRequests::class])
            ->postJson('/api/v1/ecommerce/auth/reset-password', [
                'token' => 'invalid-token',
                'email' => 'reset@example.com',
                'password' => 'brand-new-password',
                'password_confirmation' => 'brand-new-password',
            ]);

        $response->assertUnprocessable()
            ->assertJsonPath('code', 'PASSWORD_RESET_FAILED');
    }
}
