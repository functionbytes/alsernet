<?php

namespace Modules\Ecommerce\Tests\Feature\Api\V1\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Modules\Ecommerce\Events\CustomerRegistered;
use Modules\Ecommerce\Models\Customer;
use Modules\Ecommerce\Notifications\ConfirmEmailNotification;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_register_and_receives_token(): void
    {
        Notification::fake();

        $response = $this->withoutMiddleware([ThrottleRequests::class])
            ->postJson('/api/v1/ecommerce/auth/register', [
                'name' => 'Juan Perez',
                'email' => 'juan@example.com',
                'password' => 'secret-password',
                'password_confirmation' => 'secret-password',
                'phone' => '555-1234',
                'device_name' => 'Pixel 8',
                'accepts_terms' => true,
            ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'customer' => ['id', 'name', 'email'],
                    'token',
                    'tokenType',
                ],
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.tokenType', 'Bearer');

        $this->assertDatabaseHas('ecommerce_customers', ['email' => 'juan@example.com']);
        $customer = Customer::where('email', 'juan@example.com')->firstOrFail();
        $this->assertNotNull($customer->email_verification_token);
        Notification::assertSentTo($customer, ConfirmEmailNotification::class);
    }

    public function test_register_dispatches_customer_registered_event(): void
    {
        Event::fake([CustomerRegistered::class]);

        $this->withoutMiddleware([ThrottleRequests::class])
            ->postJson('/api/v1/ecommerce/auth/register', [
                'name' => 'Ana',
                'email' => 'ana@example.com',
                'password' => 'secret-password',
                'password_confirmation' => 'secret-password',
                'accepts_terms' => true,
            ]);

        Event::assertDispatched(CustomerRegistered::class);
    }

    public function test_register_rejects_duplicate_email(): void
    {
        Customer::factory()->create(['email' => 'taken@example.com']);

        $response = $this->withoutMiddleware([ThrottleRequests::class])
            ->postJson('/api/v1/ecommerce/auth/register', [
                'name' => 'Test',
                'email' => 'taken@example.com',
                'password' => 'secret-password',
                'password_confirmation' => 'secret-password',
                'accepts_terms' => true,
            ]);

        $response->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonValidationErrors('email');
    }

    public function test_register_rejects_short_password(): void
    {
        $response = $this->withoutMiddleware([ThrottleRequests::class])
            ->postJson('/api/v1/ecommerce/auth/register', [
                'name' => 'Test',
                'email' => 'test@example.com',
                'password' => 'short',
                'password_confirmation' => 'short',
                'accepts_terms' => true,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('password');
    }

    public function test_register_requires_terms_accepted(): void
    {
        $response = $this->withoutMiddleware([ThrottleRequests::class])
            ->postJson('/api/v1/ecommerce/auth/register', [
                'name' => 'Test',
                'email' => 'test@example.com',
                'password' => 'secret-password',
                'password_confirmation' => 'secret-password',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('accepts_terms');
    }
}
