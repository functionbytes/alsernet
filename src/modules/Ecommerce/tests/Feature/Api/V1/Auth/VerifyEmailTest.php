<?php

namespace Modules\Ecommerce\Tests\Feature\Api\V1\Auth;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Modules\Ecommerce\Events\CustomerEmailVerified;
use Modules\Ecommerce\Models\Customer;
use Tests\TestCase;

class VerifyEmailTest extends TestCase
{
    use DatabaseTransactions;

    public function test_verify_email_with_valid_token(): void
    {
        Event::fake([CustomerEmailVerified::class]);

        $customer = Customer::factory()->create([
            'email_verified_at' => null,
            'email_verification_token' => 'abc123token',
        ]);

        $response = $this->postJson('/api/v1/ecommerce/auth/verify-email/abc123token');

        $response->assertOk()->assertJsonPath('success', true);
        $customer->refresh();
        $this->assertNotNull($customer->email_verified_at);
        $this->assertNull($customer->email_verification_token);
        Event::assertDispatched(CustomerEmailVerified::class);
    }

    public function test_verify_email_with_invalid_token_returns_404(): void
    {
        $response = $this->postJson('/api/v1/ecommerce/auth/verify-email/wrong-token');

        $response->assertNotFound()
            ->assertJsonPath('code', 'INVALID_TOKEN');
    }

    public function test_verify_email_is_idempotent_when_already_verified(): void
    {
        $customer = Customer::factory()->create([
            'email_verified_at' => now(),
            'email_verification_token' => 'still-here',
        ]);

        $response = $this->postJson('/api/v1/ecommerce/auth/verify-email/still-here');

        $response->assertOk()->assertJsonPath('message', 'Email ya verificado.');
    }

    public function test_resend_verification_for_unverified_customer(): void
    {
        $customer = Customer::factory()->create([
            'email_verified_at' => null,
            'email_verification_token' => 'old',
        ]);
        Sanctum::actingAs($customer, ['*']);

        $response = $this->postJson('/api/v1/ecommerce/auth/resend-verification');

        $response->assertOk();
        $this->assertNotSame('old', $customer->fresh()->email_verification_token);
    }

    public function test_resend_verification_when_already_verified(): void
    {
        $customer = Customer::factory()->create(['email_verified_at' => now()]);
        Sanctum::actingAs($customer, ['*']);

        $response = $this->postJson('/api/v1/ecommerce/auth/resend-verification');

        $response->assertOk()->assertJsonPath('message', 'Email ya verificado.');
    }
}
