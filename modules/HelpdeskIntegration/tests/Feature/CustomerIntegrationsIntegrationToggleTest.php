<?php

namespace Modules\HelpdeskIntegration\Tests\Feature;

use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Setting;
use Modules\Helpdesk\Tests\HelpdeskTestCase;
use Modules\HelpdeskIntegration\Database\Seeders\HelpdeskIntegrationProvidersSeeder;
use Modules\HelpdeskIntegration\Models\CustomerIdentityVerification;

/**
 * Regression coverage for the `integration.integration_enabled` admin toggle
 * (panel/settings/helpdesk/integrations) on
 * CustomerIntegrationService::buildPayload(), the method that exposes the
 * provider catalog to the customer 360 sidebar.
 */
class CustomerIntegrationsIntegrationToggleTest extends HelpdeskTestCase
{
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(HelpdeskIntegrationProvidersSeeder::class);

        $this->customer = Customer::factory()->create(['email' => 'toggle@example.com']);

        // El gate de identidad es una preocupacion aparte del toggle de
        // integraciones: se marca verificada para aislar la variable bajo
        // prueba (el toggle), igual que ya hacen los tests de link/unlink.
        CustomerIdentityVerification::query()->create([
            'customer_id' => $this->customer->id,
            'channel' => 'email',
            'code_hash' => bcrypt('000000'),
            'expires_at' => now()->addMinutes(10),
            'verified_at' => now(),
            'verified_by' => $this->manager->id,
        ]);
    }

    protected function tearDown(): void
    {
        Setting::set('integration.integration_enabled', '1', 'integrations');

        parent::tearDown();
    }

    public function test_catalog_is_empty_when_toggle_is_disabled(): void
    {
        Setting::set('integration.integration_enabled', '0', 'integrations');

        $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.customers.integrations.show', $this->customer))
            ->assertOk()
            ->assertJsonPath('integrations', [])
            ->assertJsonPath('linkable_platforms', []);
    }

    public function test_catalog_is_populated_when_toggle_is_enabled(): void
    {
        Setting::set('integration.integration_enabled', '1', 'integrations');

        $integrations = $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.customers.integrations.show', $this->customer))
            ->assertOk()
            ->json('integrations');

        $this->assertNotEmpty($integrations);
        $this->assertContains('prestashop', array_column($integrations, 'platform'));
    }
}
