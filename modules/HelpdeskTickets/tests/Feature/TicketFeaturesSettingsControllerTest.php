<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Helpdesk\Models\Setting;
use Modules\HelpdeskTickets\Support\TicketFeatures;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TicketFeaturesSettingsControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private User $manager;

    private User $unauthorized;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-settings', 'guard_name' => 'web']);

        $this->manager = User::factory()->create();
        $this->manager->assignRole($role);

        $this->unauthorized = User::factory()->create();
    }

    // ─── TicketFeatures (catálogo) ──────────────────────────────────────────────

    public function test_defaults_are_all_true(): void
    {
        $defaults = TicketFeatures::defaults();

        $this->assertNotEmpty($defaults);
        $this->assertSame(array_fill_keys(array_keys($defaults), true), $defaults);
    }

    public function test_base_actions_are_not_in_the_catalog(): void
    {
        // "Respuesta"/"Enviar" y la pestaña "Hilo" no se pueden apagar: no
        // deben existir como keys togglables, o un admin podría desactivar
        // la única forma de contestar un ticket.
        $keys = TicketFeatures::keys();

        $this->assertNotContains('feature_reply_enabled', $keys);
        $this->assertNotContains('feature_send_enabled', $keys);
        $this->assertNotContains('feature_tab_thread_enabled', $keys);
    }

    // ─── index ────────────────────────────────────────────────────────────────

    public function test_index_renders_with_defaults_when_no_db_settings_exist(): void
    {
        $response = $this->actingAs($this->manager)
            ->get(route('manager.helpdesk.settings.tickets.features'));

        $response->assertOk();
        $response->assertViewHas('settings');
        $response->assertViewHas('sections');

        $settings = $response->viewData('settings');
        $this->assertTrue($settings['feature_composer_translate_enabled']);
    }

    public function test_index_shows_saved_settings_from_database(): void
    {
        Setting::updateOrCreate(
            ['key' => 'ticket_features.feature_composer_translate_enabled'],
            ['value' => false, 'group' => 'ticket_features']
        );

        $response = $this->actingAs($this->manager)
            ->get(route('manager.helpdesk.settings.tickets.features'));

        $response->assertOk();

        $settings = $response->viewData('settings');
        $this->assertFalse($settings['feature_composer_translate_enabled']);
        // Una key sin fila propia sigue resolviendo al default (true).
        $this->assertTrue($settings['feature_action_resolve_enabled']);
    }

    public function test_index_is_forbidden_for_unauthorized_user(): void
    {
        $this->actingAs($this->unauthorized)
            ->get(route('manager.helpdesk.settings.tickets.features'))
            ->assertForbidden();
    }

    public function test_index_redirects_unauthenticated_user(): void
    {
        $this->get(route('manager.helpdesk.settings.tickets.features'))
            ->assertRedirect(route('auth.login'));
    }

    // ─── update ───────────────────────────────────────────────────────────────

    public function test_update_saves_settings_and_redirects_back(): void
    {
        $response = $this->actingAs($this->manager)
            ->put(route('manager.helpdesk.settings.tickets.features.update'), [
                'feature_composer_translate_enabled' => '0',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('helpdesk_settings', [
            'key' => 'ticket_features.feature_composer_translate_enabled',
            'value' => false,
            'group' => 'ticket_features',
        ], 'helpdesk');
    }

    public function test_update_treats_explicit_zero_from_select_as_false(): void
    {
        // La vista usa <select> Activado/Desactivado (no checkbox): el campo
        // SIEMPRE llega en el POST. Mismo bug que ya se corrigió en
        // FeaturesSettingsController/TicketGeneralSettingsController — un
        // has() habría dado true solo por estar presente, sin mirar el valor.
        Setting::updateOrCreate(
            ['key' => 'ticket_features.feature_action_delete_enabled'],
            ['value' => true, 'group' => 'ticket_features']
        );

        $this->actingAs($this->manager)
            ->put(route('manager.helpdesk.settings.tickets.features.update'), [
                'feature_action_delete_enabled' => '0',
            ]);

        $this->assertDatabaseHas('helpdesk_settings', [
            'key' => 'ticket_features.feature_action_delete_enabled',
            'value' => false,
            'group' => 'ticket_features',
        ], 'helpdesk');
    }

    public function test_update_treats_explicit_one_from_select_as_true(): void
    {
        $this->actingAs($this->manager)
            ->put(route('manager.helpdesk.settings.tickets.features.update'), [
                'feature_action_delete_enabled' => '1',
            ]);

        $this->assertDatabaseHas('helpdesk_settings', [
            'key' => 'ticket_features.feature_action_delete_enabled',
            'value' => true,
            'group' => 'ticket_features',
        ], 'helpdesk');
    }

    public function test_update_is_forbidden_for_unauthorized_user(): void
    {
        $this->actingAs($this->unauthorized)
            ->put(route('manager.helpdesk.settings.tickets.features.update'), [
                'feature_composer_translate_enabled' => '1',
            ])
            ->assertForbidden();
    }

    public function test_update_does_not_return_json_response(): void
    {
        $response = $this->actingAs($this->manager)
            ->put(route('manager.helpdesk.settings.tickets.features.update'), [
                'feature_composer_translate_enabled' => '1',
            ]);

        $response->assertRedirect();
        $this->assertNotSame('application/json', $response->headers->get('Content-Type'));
    }
}
