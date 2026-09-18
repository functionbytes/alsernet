<?php

namespace Modules\HelpdeskIntegration\Tests\Feature\Settings;

use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Setting;
use Modules\Helpdesk\Tests\HelpdeskTestCase;
use Modules\HelpdeskIntegration\Database\Seeders\HelpdeskIntegrationPermissionsSeeder;
use Modules\HelpdeskIntegration\Database\Seeders\HelpdeskIntegrationProvidersSeeder;
use Modules\HelpdeskIntegration\Models\IntegrationProvider;
use Modules\HelpdeskIntegration\Policies\IntegrationProviderPolicy;

class ProvidersControllerTest extends HelpdeskTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(HelpdeskIntegrationPermissionsSeeder::class);
        $this->seed(HelpdeskIntegrationProvidersSeeder::class);

        $this->manager->givePermissionTo([
            'helpdeskintegration.providers.view',
            'helpdeskintegration.providers.create',
            'helpdeskintegration.providers.update',
            'helpdeskintegration.providers.delete',
            'helpdeskintegration.providers.manage',
        ]);
    }

    // ─── index ────────────────────────────────────────────────────────────────

    public function test_guest_cannot_view_providers_index(): void
    {
        $this->get(route('settings.helpdeskintegration.providers.index'))
            ->assertRedirect();
    }

    // Nota: no hay un caso HTTP "usuario autenticado sin permiso" para esta
    // pantalla — la ruta exige role:super-admin|super-settings, y AMBOS roles
    // tienen bypass total de Gate/Policy via Gate::before() (ver
    // Modules\Auth\Providers\AuthServiceProvider y Modules\Document\Providers\
    // DocumentsServiceProvider). Cualquier usuario que pase el middleware de
    // ruta automáticamente pasa también la policy. La restricción de negocio
    // real (proveedor nativo no borrable) se prueba a nivel de Policy más abajo.

    public function test_manager_can_view_providers_index(): void
    {
        $this->actingAs($this->manager)
            ->get(route('settings.helpdeskintegration.providers.index'))
            ->assertOk()
            ->assertSee('PrestaShop')
            ->assertSee('Gestión (ERP)');
    }

    // ─── store ────────────────────────────────────────────────────────────────

    public function test_manager_can_create_custom_provider(): void
    {
        $this->actingAs($this->manager)
            ->post(route('settings.helpdeskintegration.providers.store'), [
                'platform' => 'zapier',
                'label' => 'Zapier',
                'icon' => 'fas fa-bolt',
                'color' => '#ff4a00',
                'is_active' => '1',
                'is_linkable' => '0',
                'credentials' => ['api_key' => 'secret-key-123'],
            ])
            ->assertRedirect(route('settings.helpdeskintegration.providers.index'));

        $provider = IntegrationProvider::query()->where('platform', 'zapier')->firstOrFail();

        $this->assertNull($provider->driver);
        $this->assertSame('secret-key-123', $provider->credentials['api_key']);
        $this->assertDatabaseMissing('helpdesk_integration_providers', [
            'platform' => 'zapier',
            'credentials' => 'secret-key-123',
        ], 'helpdesk');
    }

    public function test_store_rejects_reserved_platform_slug(): void
    {
        $this->actingAs($this->manager)
            ->post(route('settings.helpdeskintegration.providers.store'), [
                'platform' => 'prestashop',
                'label' => 'Otro PrestaShop',
            ])
            ->assertSessionHasErrors('platform');
    }

    public function test_store_rejects_duplicate_platform(): void
    {
        IntegrationProvider::factory()->create(['platform' => 'zapier', 'driver' => null]);

        $this->actingAs($this->manager)
            ->post(route('settings.helpdeskintegration.providers.store'), [
                'platform' => 'zapier',
                'label' => 'Zapier duplicado',
            ])
            ->assertSessionHasErrors('platform');
    }

    // ─── update ───────────────────────────────────────────────────────────────

    public function test_manager_can_update_custom_provider_label_icon_color(): void
    {
        $provider = IntegrationProvider::factory()->create([
            'platform' => 'zapier',
            'driver' => null,
            'label' => 'Zapier',
        ]);

        $this->actingAs($this->manager)
            ->put(route('settings.helpdeskintegration.providers.update', $provider), [
                'platform' => 'zapier',
                'label' => 'Zapier actualizado',
                'icon' => 'fas fa-bolt',
                'color' => '#000000',
                'is_active' => '1',
                'is_linkable' => '1',
            ])
            ->assertRedirect(route('settings.helpdeskintegration.providers.index'));

        $this->assertSame('Zapier actualizado', $provider->fresh()->label);
    }

    public function test_update_cannot_change_platform_or_driver_of_native_provider(): void
    {
        $provider = IntegrationProvider::query()->where('platform', 'prestashop')->firstOrFail();

        $this->actingAs($this->manager)
            ->put(route('settings.helpdeskintegration.providers.update', $provider), [
                'label' => 'PrestaShop renombrado',
                'is_active' => '1',
                'is_linkable' => '1',
            ])
            ->assertRedirect();

        $fresh = $provider->fresh();
        $this->assertSame('prestashop', $fresh->platform);
        $this->assertSame('prestashop', $fresh->driver);
        $this->assertSame('PrestaShop renombrado', $fresh->label);
    }

    public function test_manager_can_toggle_active(): void
    {
        $provider = IntegrationProvider::query()->where('platform', 'prestashop')->firstOrFail();
        $this->assertTrue($provider->is_active);

        $this->actingAs($this->manager)
            ->post(route('settings.helpdeskintegration.providers.toggle', $provider))
            ->assertRedirect();

        $this->assertFalse($provider->fresh()->is_active);
    }

    public function test_update_credentials_blank_secret_does_not_overwrite_existing(): void
    {
        $provider = IntegrationProvider::factory()->create([
            'platform' => 'zapier',
            'driver' => null,
            'credentials' => ['api_secret' => 'keep-me'],
        ]);

        $this->actingAs($this->manager)
            ->put(route('settings.helpdeskintegration.providers.update', $provider), [
                'platform' => 'zapier',
                'label' => $provider->label,
                'is_active' => '1',
                'is_linkable' => '1',
                'credentials' => ['api_secret' => ''],
            ]);

        $this->assertSame('keep-me', $provider->fresh()->credentials['api_secret']);
    }

    public function test_edit_form_does_not_leak_decrypted_api_key(): void
    {
        $provider = IntegrationProvider::factory()->create([
            'platform' => 'zapier',
            'driver' => null,
            'credentials' => ['api_key' => 'plain-text-secret-value', 'api_secret' => 'another-secret'],
        ]);

        $this->actingAs($this->manager)
            ->get(route('settings.helpdeskintegration.providers.edit', $provider))
            ->assertOk()
            ->assertDontSee('plain-text-secret-value')
            ->assertDontSee('another-secret')
            ->assertSee('sin cambios');
    }

    // ─── destroy ──────────────────────────────────────────────────────────────

    /**
     * Probado directo contra la Policy (no via HTTP): las rutas de settings
     * exigen role:super-admin|super-settings, y ambos roles tienen bypass
     * total de Gate en esta app (ver nota en el bloque "index" arriba), asi
     * que un request HTTP nunca ejercita el rechazo de la policy.
     */
    public function test_policy_blocks_deleting_native_provider(): void
    {
        $provider = IntegrationProvider::query()->where('platform', 'prestashop')->firstOrFail();
        $this->manager->givePermissionTo('helpdeskintegration.providers.delete');

        $this->assertFalse((new IntegrationProviderPolicy)->delete($this->manager, $provider));
    }

    public function test_policy_allows_deleting_custom_provider(): void
    {
        $provider = IntegrationProvider::factory()->create(['platform' => 'zapier', 'driver' => null]);
        $this->manager->givePermissionTo('helpdeskintegration.providers.delete');

        $this->assertTrue((new IntegrationProviderPolicy)->delete($this->manager, $provider));
    }

    public function test_custom_provider_can_be_deleted(): void
    {
        $provider = IntegrationProvider::factory()->create(['platform' => 'zapier', 'driver' => null]);

        $this->actingAs($this->manager)
            ->delete(route('settings.helpdeskintegration.providers.destroy', $provider))
            ->assertRedirect(route('settings.helpdeskintegration.providers.index'));

        $this->assertDatabaseMissing('helpdesk_integration_providers', ['platform' => 'zapier'], 'helpdesk');
    }

    public function test_deleting_provider_does_not_delete_existing_customer_external_ids(): void
    {
        $customer = Customer::factory()->create();
        $customer->linkExternalId('prestashop', '4242');

        $provider = IntegrationProvider::query()->where('platform', 'prestashop')->firstOrFail();

        // Nativo: forzamos borrado directo desde el modelo para probar que la
        // ausencia de FK no arrastra el vinculo (la policy ya bloquea el
        // borrado de nativos, ver test_policy_blocks_deleting_native_provider).
        $provider->delete();

        $this->assertDatabaseHas('helpdesk_customer_external_ids', [
            'customer_id' => $customer->id,
            'platform' => 'prestashop',
            'external_id' => '4242',
        ], 'helpdesk');
    }

    // ─── identity settings (toggle SMS) ────────────────────────────────────────

    public function test_guest_cannot_update_identity_settings(): void
    {
        $this->patch(route('settings.helpdeskintegration.identity.update'), ['identity_sms_enabled' => '1'])
            ->assertRedirect();
    }

    public function test_manager_can_enable_sms_channel(): void
    {
        $this->assertFalse(helpdesk_integration_identity_sms_enabled());

        $this->actingAs($this->manager)
            ->patch(route('settings.helpdeskintegration.identity.update'), ['identity_sms_enabled' => '1'])
            ->assertRedirect(route('settings.helpdeskintegration.providers.index'));

        $this->assertTrue(helpdesk_integration_identity_sms_enabled());
    }

    public function test_manager_can_disable_sms_channel(): void
    {
        Setting::set('integration.identity_sms_enabled', '1', 'integration');
        $this->assertTrue(helpdesk_integration_identity_sms_enabled());

        $this->actingAs($this->manager)
            ->patch(route('settings.helpdeskintegration.identity.update'), ['identity_sms_enabled' => '0'])
            ->assertRedirect();

        $this->assertFalse(helpdesk_integration_identity_sms_enabled());
    }

    public function test_index_reflects_current_sms_toggle_state(): void
    {
        $this->actingAs($this->manager)
            ->get(route('settings.helpdeskintegration.providers.index'))
            ->assertOk()
            ->assertSee('Verificación de identidad');

        Setting::set('integration.identity_sms_enabled', '1', 'integration');

        $this->actingAs($this->manager)
            ->get(route('settings.helpdeskintegration.providers.index'))
            ->assertOk()
            ->assertViewHas('identitySmsEnabled', true);
    }
}
