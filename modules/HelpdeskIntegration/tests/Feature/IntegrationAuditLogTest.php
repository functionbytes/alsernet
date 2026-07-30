<?php

namespace Modules\HelpdeskIntegration\Tests\Feature;

use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Tests\HelpdeskTestCase;
use Modules\HelpdeskIntegration\Database\Seeders\HelpdeskIntegrationProvidersSeeder;
use Modules\HelpdeskIntegration\Models\CustomerIdentityVerification;
use Modules\HelpdeskIntegration\Support\IntegrationDriverRegistry;
use Modules\HelpdeskIntegration\Tests\Support\FakeIntegrationDriver;

class IntegrationAuditLogTest extends HelpdeskTestCase
{
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(HelpdeskIntegrationProvidersSeeder::class);

        // link() valida el external_id contra la plataforma via driver->resync();
        // fake determinista para no depender del bridge PrestaShop real.
        FakeIntegrationDriver::reset();
        app(IntegrationDriverRegistry::class)->register('prestashop', FakeIntegrationDriver::class);

        $this->customer = Customer::factory()->create(['email' => 'audit@example.com']);

        CustomerIdentityVerification::query()->create([
            'customer_id' => $this->customer->id,
            'channel' => 'email',
            'code_hash' => bcrypt('000000'),
            'expires_at' => now()->addMinutes(10),
            'verified_at' => now(),
            'verified_by' => $this->manager->id,
        ]);
    }

    public function test_linking_writes_audit_entry(): void
    {
        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.customers.integrations.link', $this->customer), [
                'platform' => 'prestashop',
                'external_id' => '4242',
            ]);

        $this->assertDatabaseHas('helpdesk_integration_audit_log', [
            'customer_id' => $this->customer->id,
            'platform' => 'prestashop',
            'action' => 'linked',
            'external_id' => '4242',
            'user_id' => $this->manager->id,
        ], 'helpdesk');
    }

    public function test_unlinking_writes_audit_entry(): void
    {
        $this->customer->linkExternalId('prestashop', '4242');

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.customers.integrations.unlink', $this->customer), [
                'platform' => 'prestashop',
            ]);

        $this->assertDatabaseHas('helpdesk_integration_audit_log', [
            'customer_id' => $this->customer->id,
            'platform' => 'prestashop',
            'action' => 'unlinked',
        ], 'helpdesk');
    }

    public function test_show_exposes_last_activity_summary(): void
    {
        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.customers.integrations.link', $this->customer), [
                'platform' => 'prestashop',
                'external_id' => '4242',
            ]);

        $resp = $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.customers.integrations.show', $this->customer))
            ->assertOk()
            ->json('last_activity');

        $this->assertNotNull($resp);
        // El resumen viene del lang file (es/en según locale del entorno).
        $this->assertStringContainsString(
            __('helpdeskintegration::messages.audit.linked', ['label' => 'PrestaShop']),
            $resp
        );
    }

    public function test_last_activity_is_null_when_no_history(): void
    {
        $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.customers.integrations.show', $this->customer))
            ->assertOk()
            ->assertJsonPath('last_activity', null);
    }
}
