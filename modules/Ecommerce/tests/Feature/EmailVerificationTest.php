<?php

namespace Modules\Ecommerce\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Modules\Ecommerce\Events\CustomerRegistered;
use Modules\Ecommerce\Models\Customer;
use Modules\Ecommerce\Notifications\ConfirmEmailNotification;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_registration_sends_verification_email(): void
    {
        Notification::fake();

        $response = $this->withoutMiddleware([ThrottleRequests::class])
            ->post(route('ecommerce.register.post'), [
                'name' => 'Juan Perez',
                'email' => 'juan@example.com',
                'phone' => '555-1234',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

        $response->assertRedirect();

        $customer = Customer::where('email', 'juan@example.com')->first();
        $this->assertNotNull($customer);
        $this->assertNotNull($customer->email_verification_token);
        Notification::assertSentTo($customer, ConfirmEmailNotification::class);
    }

    public function test_email_verification_marks_customer_verified(): void
    {
        $customer = Customer::factory()->create([
            'email_verified_at' => null,
            'email_verification_token' => 'valid_token_abc',
        ]);

        $response = $this->get('/tienda/confirm-email?token=valid_token_abc');

        $customer->refresh();
        $this->assertNotNull($customer->email_verified_at);
        $this->assertNull($customer->email_verification_token);
        $response->assertRedirect();
    }

    public function test_email_verification_with_invalid_token_fails(): void
    {
        $response = $this->get('/tienda/confirm-email?token=invalid_xxx');

        $response->assertRedirect();
    }

    public function test_authenticated_unverified_customer_can_resend_verification(): void
    {
        Notification::fake();

        $customer = Customer::factory()->create([
            'email_verified_at' => null,
            'email_verification_token' => 'old_token',
        ]);

        $this->actingAs($customer, 'ecommerce')
            ->post('/tienda/resend-verification');

        Notification::assertSentTo($customer, ConfirmEmailNotification::class);
        $customer->refresh();
        $this->assertNotEquals('old_token', $customer->email_verification_token);
    }

    public function test_customer_registered_event_is_dispatched_on_register(): void
    {
        Event::fake([CustomerRegistered::class]);

        $this->withoutMiddleware([ThrottleRequests::class])
            ->post(route('ecommerce.register.post'), [
                'name' => 'Ana',
                'email' => 'ana@example.com',
                'phone' => '555-9999',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

        Event::assertDispatched(CustomerRegistered::class);
    }
}
