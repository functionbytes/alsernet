<?php

namespace Modules\HelpdeskIntegration\Tests\Feature;

use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Tests\HelpdeskTestCase;
use Modules\HelpdeskIntegration\Database\Seeders\HelpdeskIntegrationProvidersSeeder;
use Modules\HelpdeskIntegration\Support\IntegrationDriverRegistry;
use Modules\HelpdeskIntegration\Tests\Support\FakeIntegrationDriver;

/**
 * detail() (ficha de una plataforma ya vinculada, widget del panel derecho)
 * ahora valida `platform` contra el catálogo con las mismas reglas que
 * search(), en vez de dejarlo pasar sin comprobar y caer siempre en la rama
 * "no vinculado".
 */
class CustomerIntegrationDetailTest extends HelpdeskTestCase
{
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::factory()->create(['email' => 'detail.test@example.com']);

        $this->seed(HelpdeskIntegrationProvidersSeeder::class);
        FakeIntegrationDriver::reset();
        app(IntegrationDriverRegistry::class)->register('prestashop', FakeIntegrationDriver::class);
    }

    public function test_unknown_platform_returns_validation_error(): void
    {
        $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.customers.integrations.detail', [
                'customer' => $this->customer,
                'platform' => 'not-a-real-platform',
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['platform']);
    }

    public function test_known_but_unlinked_platform_returns_not_found_message(): void
    {
        $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.customers.integrations.detail', [
                'customer' => $this->customer,
                'platform' => 'prestashop',
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['platform']);
    }

    public function test_guest_cannot_view_detail(): void
    {
        $this->getJson(route('manager.helpdesk.customers.integrations.detail', [
            'customer' => $this->customer,
            'platform' => 'prestashop',
        ]))->assertUnauthorized();
    }

    public function test_linked_platform_returns_detail_payload(): void
    {
        $this->customer->linkExternalId('prestashop', '123');

        $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.customers.integrations.detail', [
                'customer' => $this->customer,
                'platform' => 'prestashop',
            ]))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('external_id', '123')
            ->assertJsonPath('available', true);
    }
}
