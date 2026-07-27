<?php

namespace Modules\HelpdeskIntegration\Tests\Feature;

use App\Models\User;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Tests\HelpdeskTestCase;
use Modules\HelpdeskIntegration\Database\Seeders\HelpdeskIntegrationProvidersSeeder;
use Modules\HelpdeskIntegration\Models\CustomerIdentityVerification;
use Modules\HelpdeskIntegration\Models\IntegrationProvider;
use Modules\HelpdeskIntegration\Support\IntegrationDriverRegistry;
use Modules\HelpdeskIntegration\Tests\Support\FakeIntegrationDriver;

class CustomerIntegrationsControllerTest extends HelpdeskTestCase
{
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(HelpdeskIntegrationProvidersSeeder::class);

        // link() ahora verifica el external_id contra la plataforma remota via
        // driver->resync(); se sustituyen los drivers reales (ERP Oracle /
        // bridge PrestaShop) por un fake determinista que responde "existe".
        FakeIntegrationDriver::reset();
        $registry = app(IntegrationDriverRegistry::class);
        $registry->register('prestashop', FakeIntegrationDriver::class);
        $registry->register('erp', FakeIntegrationDriver::class);

        $this->customer = Customer::factory()->create(['email' => 'test@example.com']);

        // El gate de identidad se prueba por separado en
        // CustomerIdentityVerificationTest — aqui se marca verificada para
        // poder probar la mecanica de vincular/desvincular/buscar sin ruido.
        $this->verifyCustomerIdentity($this->customer);
    }

    private function verifyCustomerIdentity(Customer $customer): void
    {
        CustomerIdentityVerification::query()->create([
            'customer_id' => $customer->id,
            'channel' => 'email',
            'code_hash' => bcrypt('000000'),
            'expires_at' => now()->addMinutes(10),
            'verified_at' => now(),
            'verified_by' => $this->manager->id,
        ]);
    }

    // ─── show ─────────────────────────────────────────────────────────────────

    public function test_guest_cannot_view_integrations(): void
    {
        $this->getJson(route('manager.helpdesk.customers.integrations.show', $this->customer))
            ->assertUnauthorized();
    }

    public function test_user_without_permission_cannot_view_integrations(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson(route('manager.helpdesk.customers.integrations.show', $this->customer))
            ->assertForbidden();
    }

    public function test_manager_can_view_integrations(): void
    {
        $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.customers.integrations.show', $this->customer))
            ->assertOk()
            ->assertJsonStructure([
                'success',
                'customer' => ['name', 'email'],
                'integrations' => [['platform', 'label', 'icon', 'color', 'connected', 'external_id']],
                'linkable_platforms' => [['platform', 'label', 'icon', 'color', 'search_types']],
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('customer.email', 'test@example.com');
    }

    public function test_show_always_includes_prestashop_and_erp_platforms(): void
    {
        $resp = $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.customers.integrations.show', $this->customer))
            ->assertOk()
            ->json('integrations');

        $platforms = array_column($resp, 'platform');
        $this->assertContains('prestashop', $platforms);
        $this->assertContains('erp', $platforms);
    }

    public function test_show_reflects_existing_external_ids(): void
    {
        $this->customer->linkExternalId('prestashop', '9999', ['linked_via' => 'test']);

        $resp = $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.customers.integrations.show', $this->customer))
            ->assertOk()
            ->json('integrations');

        $ps = collect($resp)->firstWhere('platform', 'prestashop');
        $this->assertTrue($ps['connected']);
        $this->assertSame('9999', $ps['external_id']);
    }

    public function test_show_includes_icon_color_from_provider_row(): void
    {
        $resp = $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.customers.integrations.show', $this->customer))
            ->assertOk()
            ->json('integrations');

        $ps = collect($resp)->firstWhere('platform', 'prestashop');
        $this->assertSame('fas fa-cart-shopping', $ps['icon']);
        $this->assertSame('#df1d1d', $ps['color']);
    }

    // ─── sync ─────────────────────────────────────────────────────────────────

    public function test_guest_cannot_sync_integrations(): void
    {
        $this->postJson(route('manager.helpdesk.customers.integrations.sync', $this->customer))
            ->assertUnauthorized();
    }

    public function test_user_without_permission_cannot_sync_integrations(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('manager.helpdesk.customers.integrations.sync', $this->customer))
            ->assertForbidden();
    }

    public function test_sync_returns_linked_false_when_no_platforms_connected(): void
    {
        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.customers.integrations.sync', $this->customer))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('linked', false);
    }

    public function test_manager_can_call_sync_and_gets_valid_response(): void
    {
        $this->customer->linkExternalId('prestashop', '4242');

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.customers.integrations.sync', $this->customer))
            ->assertOk()
            ->assertJsonPath('linked', true)
            ->assertJsonStructure(['success', 'message', 'linked', 'integrations', 'linkable_platforms']);
    }

    // ─── search ───────────────────────────────────────────────────────────────

    public function test_guest_cannot_search_integrations(): void
    {
        $this->getJson(route('manager.helpdesk.customers.integrations.search', $this->customer).'?platform=prestashop')
            ->assertUnauthorized();
    }

    public function test_user_without_permission_cannot_search(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson(route('manager.helpdesk.customers.integrations.search', $this->customer).'?platform=prestashop')
            ->assertForbidden();
    }

    public function test_search_rejects_unknown_platform(): void
    {
        $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.customers.integrations.search', $this->customer).'?platform=shopify&q=garcia')
            ->assertUnprocessable();
    }

    public function test_search_with_short_query_returns_empty_results(): void
    {
        $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.customers.integrations.search', $this->customer).'?platform=prestashop&q=a&type=email')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('results', []);
    }

    public function test_search_prestashop_returns_valid_structure(): void
    {
        // PS DB no disponible en tests → results vacíos, estructura correcta
        $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.customers.integrations.search', $this->customer).'?platform=prestashop&q=garcia&type=name')
            ->assertOk()
            ->assertJsonStructure(['success', 'results']);
    }

    public function test_search_erp_returns_valid_structure(): void
    {
        // ERP manager no disponible en tests → results vacíos, estructura correcta
        $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.customers.integrations.search', $this->customer).'?platform=erp&q=test@example.com&type=email')
            ->assertOk()
            ->assertJsonStructure(['success', 'results']);
    }

    public function test_search_returns_empty_when_driver_unavailable(): void
    {
        IntegrationProvider::query()->create([
            'platform' => 'ghost',
            'driver' => 'ghost-driver-not-registered',
            'label' => 'Ghost',
            'is_active' => true,
            'is_linkable' => true,
        ]);

        $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.customers.integrations.search', $this->customer).'?platform=ghost&q=garcia')
            ->assertOk()
            ->assertJsonPath('results', []);
    }

    public function test_search_custom_provider_without_driver_returns_empty(): void
    {
        IntegrationProvider::query()->create([
            'platform' => 'webhook-custom',
            'driver' => null,
            'label' => 'Webhook custom',
            'is_active' => true,
            'is_linkable' => true,
        ]);

        $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.customers.integrations.search', $this->customer).'?platform=webhook-custom&q=garcia')
            ->assertOk()
            ->assertJsonPath('results', []);
    }

    // ─── link ─────────────────────────────────────────────────────────────────

    public function test_guest_cannot_link_integration(): void
    {
        $this->postJson(route('manager.helpdesk.customers.integrations.link', $this->customer), [
            'platform' => 'prestashop',
            'external_id' => '123',
        ])->assertUnauthorized();
    }

    public function test_user_without_permission_cannot_link(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('manager.helpdesk.customers.integrations.link', $this->customer), [
                'platform' => 'prestashop',
                'external_id' => '123',
            ])->assertForbidden();
    }

    public function test_link_rejects_invalid_platform(): void
    {
        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.customers.integrations.link', $this->customer), [
                'platform' => 'shopify',
                'external_id' => '123',
            ])->assertUnprocessable()
            ->assertJsonPath('success', false);
    }

    public function test_link_rejects_empty_external_id(): void
    {
        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.customers.integrations.link', $this->customer), [
                'platform' => 'prestashop',
                'external_id' => '',
            ])->assertUnprocessable()
            ->assertJsonPath('success', false);
    }

    public function test_link_rejects_platform_marked_not_linkable(): void
    {
        IntegrationProvider::query()->where('platform', 'prestashop')->update(['is_linkable' => false]);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.customers.integrations.link', $this->customer), [
                'platform' => 'prestashop',
                'external_id' => '123',
            ])->assertUnprocessable();
    }

    public function test_link_rejects_inactive_platform(): void
    {
        IntegrationProvider::query()->where('platform', 'prestashop')->update(['is_active' => false]);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.customers.integrations.link', $this->customer), [
                'platform' => 'prestashop',
                'external_id' => '123',
            ])->assertUnprocessable();
    }

    public function test_manager_can_link_customer_to_prestashop(): void
    {
        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.customers.integrations.link', $this->customer), [
                'platform' => 'prestashop',
                'external_id' => '4242',
            ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'message', 'integrations']);

        $this->assertDatabaseHas('helpdesk_customer_external_ids', [
            'customer_id' => $this->customer->id,
            'platform' => 'prestashop',
            'external_id' => '4242',
        ], 'helpdesk');
    }

    public function test_manager_can_link_customer_to_erp(): void
    {
        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.customers.integrations.link', $this->customer), [
                'platform' => 'erp',
                'external_id' => '9999',
            ])->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('helpdesk_customer_external_ids', [
            'customer_id' => $this->customer->id,
            'platform' => 'erp',
            'external_id' => '9999',
        ], 'helpdesk');
    }

    public function test_search_flags_platform_error_when_driver_fails(): void
    {
        FakeIntegrationDriver::$platformUp = false;

        $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.customers.integrations.search', [
                'customer' => $this->customer->id,
                'platform' => 'prestashop',
                'q' => 'juan@example.com',
                'type' => 'email',
            ]))
            ->assertOk()
            ->assertJsonPath('platform_error', true)
            ->assertJsonPath('results', []);
    }

    public function test_search_without_results_does_not_flag_platform_error(): void
    {
        FakeIntegrationDriver::$searchResults = [];

        $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.customers.integrations.search', [
                'customer' => $this->customer->id,
                'platform' => 'prestashop',
                'q' => 'nadie@example.com',
                'type' => 'email',
            ]))
            ->assertOk()
            ->assertJsonPath('platform_error', false)
            ->assertJsonPath('results', []);
    }

    public function test_sync_marks_link_as_not_found_when_id_disappeared_from_platform(): void
    {
        $this->customer->linkExternalId('prestashop', '4242');
        FakeIntegrationDriver::$resyncResult = false; // plataforma responde, ID no existe

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.customers.integrations.sync-platform', [$this->customer, 'prestashop']))
            ->assertOk();

        $this->assertDatabaseHas('helpdesk_customer_external_ids', [
            'customer_id' => $this->customer->id,
            'platform' => 'prestashop',
            'sync_status' => 'not_found',
        ], 'helpdesk');
    }

    public function test_sync_marks_link_as_error_when_platform_is_down(): void
    {
        $this->customer->linkExternalId('prestashop', '4242');
        FakeIntegrationDriver::$platformUp = false;

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.customers.integrations.sync-platform', [$this->customer, 'prestashop']))
            ->assertOk();

        $this->assertDatabaseHas('helpdesk_customer_external_ids', [
            'customer_id' => $this->customer->id,
            'platform' => 'prestashop',
            'sync_status' => 'error',
        ], 'helpdesk');
    }

    public function test_link_rejects_when_platform_is_down(): void
    {
        FakeIntegrationDriver::$platformUp = false;

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.customers.integrations.link', $this->customer), [
                'platform' => 'prestashop',
                'external_id' => '4242',
            ])->assertUnprocessable()
            ->assertJsonValidationErrors(['external_id']);

        $this->assertDatabaseMissing('helpdesk_customer_external_ids', [
            'customer_id' => $this->customer->id,
            'platform' => 'prestashop',
        ], 'helpdesk');
    }

    public function test_link_rejects_external_id_that_does_not_exist_on_platform(): void
    {
        FakeIntegrationDriver::$resyncResult = false;

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.customers.integrations.link', $this->customer), [
                'platform' => 'prestashop',
                'external_id' => '999999',
            ])->assertUnprocessable()
            ->assertJsonValidationErrors(['external_id']);

        $this->assertDatabaseMissing('helpdesk_customer_external_ids', [
            'customer_id' => $this->customer->id,
            'platform' => 'prestashop',
        ], 'helpdesk');
    }

    public function test_link_allows_custom_platform_without_driver(): void
    {
        $provider = IntegrationProvider::factory()->create([
            'platform' => 'miplataforma',
            'driver' => null,
        ]);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.customers.integrations.link', $this->customer), [
                'platform' => $provider->platform,
                'external_id' => 'CUST-77',
            ])->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('helpdesk_customer_external_ids', [
            'customer_id' => $this->customer->id,
            'platform' => 'miplataforma',
            'external_id' => 'CUST-77',
        ], 'helpdesk');
    }

    public function test_link_is_idempotent_for_same_platform_and_id(): void
    {
        $this->customer->linkExternalId('prestashop', '4242');

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.customers.integrations.link', $this->customer), [
                'platform' => 'prestashop',
                'external_id' => '4242',
            ])->assertOk();

        $this->assertDatabaseCount('helpdesk_customer_external_ids', 1, 'helpdesk');
    }

    public function test_link_response_shows_integration_as_connected(): void
    {
        $resp = $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.customers.integrations.link', $this->customer), [
                'platform' => 'prestashop',
                'external_id' => '7777',
            ])->assertOk()
            ->json('integrations');

        $ps = collect($resp)->firstWhere('platform', 'prestashop');
        $this->assertTrue($ps['connected']);
        $this->assertSame('7777', $ps['external_id']);
    }

    // ─── unlink ───────────────────────────────────────────────────────────────

    public function test_guest_cannot_unlink_integration(): void
    {
        $this->postJson(route('manager.helpdesk.customers.integrations.unlink', $this->customer), [
            'platform' => 'prestashop',
        ])->assertUnauthorized();
    }

    public function test_user_without_permission_cannot_unlink(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('manager.helpdesk.customers.integrations.unlink', $this->customer), [
                'platform' => 'prestashop',
            ])->assertForbidden();
    }

    public function test_unlink_rejects_invalid_platform(): void
    {
        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.customers.integrations.unlink', $this->customer), [
                'platform' => 'woocommerce',
            ])->assertUnprocessable()
            ->assertJsonPath('success', false);
    }

    public function test_manager_can_unlink_customer_from_prestashop(): void
    {
        $this->customer->linkExternalId('prestashop', '4242');

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.customers.integrations.unlink', $this->customer), [
                'platform' => 'prestashop',
            ])->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('helpdesk_customer_external_ids', [
            'customer_id' => $this->customer->id,
            'platform' => 'prestashop',
        ], 'helpdesk');
    }

    public function test_manager_can_unlink_customer_from_erp(): void
    {
        $this->customer->linkExternalId('erp', '9999');

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.customers.integrations.unlink', $this->customer), [
                'platform' => 'erp',
            ])->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('helpdesk_customer_external_ids', [
            'customer_id' => $this->customer->id,
            'platform' => 'erp',
        ], 'helpdesk');
    }

    public function test_unlink_is_safe_when_no_link_exists(): void
    {
        // No debe fallar si no existe el vínculo
        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.customers.integrations.unlink', $this->customer), [
                'platform' => 'prestashop',
            ])->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_unlink_response_shows_integration_as_disconnected(): void
    {
        $this->customer->linkExternalId('prestashop', '4242');

        $resp = $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.customers.integrations.unlink', $this->customer), [
                'platform' => 'prestashop',
            ])->assertOk()
            ->json('integrations');

        $ps = collect($resp)->firstWhere('platform', 'prestashop');
        $this->assertFalse($ps['connected']);
        $this->assertNull($ps['external_id']);
    }

    public function test_unlink_only_removes_specified_platform(): void
    {
        $this->customer->linkExternalId('prestashop', '4242');
        $this->customer->linkExternalId('erp', '9999');

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.customers.integrations.unlink', $this->customer), [
                'platform' => 'prestashop',
            ])->assertOk();

        $this->assertDatabaseMissing('helpdesk_customer_external_ids', [
            'customer_id' => $this->customer->id,
            'platform' => 'prestashop',
        ], 'helpdesk');
        $this->assertDatabaseHas('helpdesk_customer_external_ids', [
            'customer_id' => $this->customer->id,
            'platform' => 'erp',
            'external_id' => '9999',
        ], 'helpdesk');
    }
}
