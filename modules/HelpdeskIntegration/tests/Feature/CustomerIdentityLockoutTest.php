<?php

namespace Modules\HelpdeskIntegration\Tests\Feature;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Tests\HelpdeskTestCase;
use Modules\HelpdeskIntegration\Models\CustomerIdentityVerification;
use Modules\HelpdeskIntegration\Models\IntegrationAuditLog;
use Modules\HelpdeskIntegration\Services\CustomerIdentityVerificationService;

class CustomerIdentityLockoutTest extends HelpdeskTestCase
{
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::factory()->create(['email' => 'lockout@example.com']);

        // Aisla el estado del rate limiter entre tests: la clave incluye el
        // customer_id + IP, pero el mismo customer_id podria colisionar si
        // otro test dejo hits sin limpiar (RateLimiter usa cache, no BD).
        RateLimiter::clear('auth:customer_identity:'.sha1($this->customer->id.'|127.0.0.1'));
        RateLimiter::clear('auth:customer_identity_request:'.sha1($this->customer->id.'|127.0.0.1'));
    }

    private function requestRealCode(): string
    {
        app(CustomerIdentityVerificationService::class)->requestCode($this->customer, 'email');
        $verification = CustomerIdentityVerification::query()->latest('id')->first();

        $code = '123456';
        $verification->forceFill(['code_hash' => Hash::make($code)])->save();

        return $code;
    }

    public function test_blocks_after_max_attempts(): void
    {
        $this->requestRealCode();

        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($this->manager)
                ->postJson(route('manager.helpdesk.customers.identity.verify', $this->customer), ['code' => '000000'])
                ->assertUnprocessable();
        }

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.customers.identity.verify', $this->customer), ['code' => '000000'])
            ->assertStatus(429)
            ->assertJsonStructure(['success', 'message', 'locked_seconds']);
    }

    public function test_error_response_exposes_attempts_made(): void
    {
        $this->requestRealCode();

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.customers.identity.verify', $this->customer), ['code' => '000000'])
            ->assertUnprocessable()
            ->assertJsonPath('attempts_made', 1)
            ->assertJsonPath('max_attempts', 3);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.customers.identity.verify', $this->customer), ['code' => '000000'])
            ->assertUnprocessable()
            ->assertJsonPath('attempts_made', 2);
    }

    public function test_correct_code_still_succeeds_before_reaching_lockout(): void
    {
        $code = $this->requestRealCode();

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.customers.identity.verify', $this->customer), ['code' => '000000'])
            ->assertUnprocessable();

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.customers.identity.verify', $this->customer), ['code' => $code])
            ->assertOk()
            ->assertJsonPath('identity.verified', true)
            ->assertJsonPath('identity.channel', 'email')
            ->assertJsonStructure(['identity' => ['verified_at', 'verified_by', 'expires_at']]);
    }

    public function test_successful_verification_clears_the_rate_limit(): void
    {
        $code = $this->requestRealCode();

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.customers.identity.verify', $this->customer), ['code' => '000000'])
            ->assertUnprocessable();

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.customers.identity.verify', $this->customer), ['code' => $code])
            ->assertOk();

        // Tras el exito, una nueva ronda de intentos fallidos empieza desde 0
        // (no hereda el hit previo a la limpieza).
        app(CustomerIdentityVerificationService::class)->requestCode($this->customer, 'email');
        $verification = CustomerIdentityVerification::query()->latest('id')->first();
        $verification->forceFill(['code_hash' => Hash::make('654321')])->save();

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.customers.identity.verify', $this->customer), ['code' => '000000'])
            ->assertUnprocessable()
            ->assertJsonPath('attempts_made', 1);
    }

    // ─── rate limit de solicitud de codigo (requestIdentity) ───────────────────

    public function test_request_code_is_rate_limited_after_max_attempts(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($this->manager)
                ->postJson(route('manager.helpdesk.customers.identity.request', $this->customer), ['channel' => 'email'])
                ->assertOk();
        }

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.customers.identity.request', $this->customer), ['channel' => 'email'])
            ->assertStatus(429)
            ->assertJsonStructure(['success', 'message', 'locked_seconds']);
    }

    public function test_request_without_destination_does_not_count_towards_the_limit(): void
    {
        $noEmail = Customer::factory()->create(['email' => null]);
        RateLimiter::clear('auth:customer_identity_request:'.sha1($noEmail->id.'|127.0.0.1'));

        // Sin email, cada intento falla con 422 y no deberia consumir el
        // limite (nada se llega a enviar).
        for ($i = 0; $i < 6; $i++) {
            $this->actingAs($this->manager)
                ->postJson(route('manager.helpdesk.customers.identity.request', $noEmail), ['channel' => 'email'])
                ->assertUnprocessable();
        }
    }

    // ─── rastro en el log de auditoria ─────────────────────────────────────────

    public function test_successful_verification_is_logged_to_the_audit_log(): void
    {
        $code = $this->requestRealCode();

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.customers.identity.verify', $this->customer), ['code' => $code])
            ->assertOk();

        $this->assertDatabaseHas('helpdesk_integration_audit_log', [
            'customer_id' => $this->customer->id,
            'platform' => 'identity',
            'action' => 'identity_verified',
            'external_id' => 'email',
            'user_id' => $this->manager->id,
        ], 'helpdesk');
    }

    public function test_lockout_is_logged_to_the_audit_log_exactly_once(): void
    {
        $this->requestRealCode();

        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($this->manager)
                ->postJson(route('manager.helpdesk.customers.identity.verify', $this->customer), ['code' => '000000'])
                ->assertUnprocessable();
        }

        // Una cuarta peticion ya bloqueada (429) no debe generar una segunda
        // entrada de auditoria — el evento se registra una sola vez, al
        // momento exacto en que se agota el cupo.
        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.customers.identity.verify', $this->customer), ['code' => '000000'])
            ->assertStatus(429);

        $this->assertSame(1, IntegrationAuditLog::query()
            ->where('customer_id', $this->customer->id)
            ->where('action', 'identity_locked')
            ->count());
    }

    public function test_failed_attempts_below_lockout_are_not_logged(): void
    {
        $this->requestRealCode();

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.customers.identity.verify', $this->customer), ['code' => '000000'])
            ->assertUnprocessable();

        $this->assertDatabaseMissing('helpdesk_integration_audit_log', [
            'customer_id' => $this->customer->id,
            'action' => 'identity_locked',
        ], 'helpdesk');
    }
}
