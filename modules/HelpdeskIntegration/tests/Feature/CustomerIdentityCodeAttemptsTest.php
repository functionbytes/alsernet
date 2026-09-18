<?php

namespace Modules\HelpdeskIntegration\Tests\Feature;

use Illuminate\Support\Facades\Hash;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Tests\HelpdeskTestCase;
use Modules\HelpdeskIntegration\Models\CustomerIdentityVerification;
use Modules\HelpdeskIntegration\Services\CustomerIdentityVerificationService;

/**
 * Límite de intentos fallidos POR CÓDIGO (persistido en BD), complementario
 * al AuthRateLimiter en cache: aquel se reinicia con su decay y se diluye
 * rotando IPs; este contador quema el código a los 5 fallos y obliga a
 * solicitar uno nuevo.
 */
class CustomerIdentityCodeAttemptsTest extends HelpdeskTestCase
{
    private Customer $customer;

    private CustomerIdentityVerificationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::factory()->create(['email' => 'otp@example.com']);
        $this->service = app(CustomerIdentityVerificationService::class);
    }

    private function createPendingCode(string $code = '123456'): CustomerIdentityVerification
    {
        return CustomerIdentityVerification::query()->create([
            'customer_id' => $this->customer->id,
            'channel' => 'email',
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(10),
        ]);
    }

    public function test_failed_attempts_are_persisted_per_code(): void
    {
        $verification = $this->createPendingCode();

        $this->assertFalse($this->service->confirmCode($this->customer, '000000'));
        $this->assertFalse($this->service->confirmCode($this->customer, '111111'));

        $this->assertSame(2, $verification->fresh()->attempts);
    }

    public function test_code_is_burned_after_max_failed_attempts(): void
    {
        $verification = $this->createPendingCode('123456');

        foreach (['000000', '111111', '222222', '333333', '444444'] as $wrong) {
            $this->assertFalse($this->service->confirmCode($this->customer, $wrong));
        }

        // El código correcto ya no sirve: se quemó al agotar los 5 intentos.
        $this->assertFalse($this->service->confirmCode($this->customer, '123456'));

        $fresh = $verification->fresh();
        $this->assertSame(5, $fresh->attempts);
        $this->assertTrue($fresh->expires_at->isPast());
        $this->assertFalse($this->service->isVerified($this->customer));
    }

    public function test_correct_code_within_attempt_budget_verifies(): void
    {
        $this->createPendingCode('123456');

        $this->assertFalse($this->service->confirmCode($this->customer, '000000'));
        $this->assertFalse($this->service->confirmCode($this->customer, '111111'));
        $this->assertTrue($this->service->confirmCode($this->customer, '123456'));

        $this->assertTrue($this->service->isVerified($this->customer));
    }

    public function test_verify_records_conversation_and_exposes_it_in_summary(): void
    {
        $conversation = Conversation::factory()->create(['customer_id' => $this->customer->id]);
        $this->createPendingCode('123456');

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.customers.identity.verify', $this->customer), [
                'code' => '123456',
                'conversation_id' => $conversation->id,
            ])
            ->assertOk()
            ->assertJsonPath('identity.conversation_id', $conversation->id);

        $this->assertDatabaseHas('helpdesk_customer_identity_verifications', [
            'customer_id' => $this->customer->id,
            'conversation_id' => $conversation->id,
        ], 'helpdesk');
    }

    public function test_verify_rejects_unknown_conversation(): void
    {
        $this->createPendingCode('123456');

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.customers.identity.verify', $this->customer), [
                'code' => '123456',
                'conversation_id' => 999999,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['conversation_id']);
    }

    public function test_new_code_after_burn_starts_with_fresh_attempt_budget(): void
    {
        $this->createPendingCode('123456');

        foreach (range(1, 5) as $i) {
            $this->service->confirmCode($this->customer, '000000');
        }

        // Un código nuevo (reenvío) vuelve a aceptar la verificación.
        $this->createPendingCode('654321');

        $this->assertTrue($this->service->confirmCode($this->customer, '654321'));
    }
}
