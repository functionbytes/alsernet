<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Helpdesk\Models\Setting;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TicketGeneralSettingsControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

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

    // ─── index ────────────────────────────────────────────────────────────────

    public function test_index_renders_with_defaults_when_no_db_settings_exist(): void
    {
        $response = $this->actingAs($this->manager)
            ->get(route('manager.helpdesk.settings.tickets.general'));

        $response->assertOk();
        $response->assertViewHas('settings');

        $settings = $response->viewData('settings');
        $this->assertSame('SPT', $settings['customer_ticketid']);
        $this->assertSame(100, $settings['ticket_character']);
    }

    public function test_index_shows_saved_settings_from_database(): void
    {
        Setting::updateOrCreate(
            ['key' => 'tickets.customer_ticketid'],
            ['value' => 'MYCO', 'group' => 'tickets']
        );
        Setting::updateOrCreate(
            ['key' => 'tickets.ticket_character'],
            ['value' => '250', 'group' => 'tickets']
        );

        $response = $this->actingAs($this->manager)
            ->get(route('manager.helpdesk.settings.tickets.general'));

        $response->assertOk();

        $settings = $response->viewData('settings');
        $this->assertSame('MYCO', $settings['customer_ticketid']);
        $this->assertSame('250', $settings['ticket_character']);
    }

    public function test_index_is_forbidden_for_unauthorized_user(): void
    {
        $this->actingAs($this->unauthorized)
            ->get(route('manager.helpdesk.settings.tickets.general'))
            ->assertForbidden();
    }

    public function test_index_redirects_unauthenticated_user(): void
    {
        $this->get(route('manager.helpdesk.settings.tickets.general'))
            ->assertRedirect(route('auth.login'));
    }

    // ─── update ───────────────────────────────────────────────────────────────

    public function test_update_saves_settings_and_redirects_back(): void
    {
        $response = $this->actingAs($this->manager)
            ->put(route('manager.helpdesk.settings.tickets.general.update'), [
                'customer_ticketid' => 'TICK',
                'ticket_character' => 200,
                'guest_ticket' => '1',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('helpdesk_settings', [
            'key' => 'tickets.customer_ticketid',
            'value' => 'TICK',
            'group' => 'tickets',
        ], 'helpdesk');
        $this->assertDatabaseHas('helpdesk_settings', [
            'key' => 'tickets.ticket_character',
            'value' => 200,
            'group' => 'tickets',
        ], 'helpdesk');
    }

    public function test_update_treats_missing_checkbox_as_false(): void
    {
        // Seed an existing truthy value
        Setting::updateOrCreate(
            ['key' => 'tickets.guest_ticket'],
            ['value' => '1', 'group' => 'tickets']
        );

        // Submit without guest_ticket checkbox (unchecked)
        $this->actingAs($this->manager)
            ->put(route('manager.helpdesk.settings.tickets.general.update'), [
                'customer_ticketid' => 'SPT',
                'ticket_character' => 100,
                // guest_ticket intentionally omitted
            ]);

        $this->assertDatabaseHas('helpdesk_settings', [
            'key' => 'tickets.guest_ticket',
            'value' => false,
            'group' => 'tickets',
        ], 'helpdesk');
    }

    public function test_update_requires_customer_ticketid(): void
    {
        $this->actingAs($this->manager)
            ->put(route('manager.helpdesk.settings.tickets.general.update'), [
                'ticket_character' => 100,
                // customer_ticketid missing
            ])
            ->assertSessionHasErrors('customer_ticketid');
    }

    public function test_update_rejects_ticketid_longer_than_4_chars(): void
    {
        $this->actingAs($this->manager)
            ->put(route('manager.helpdesk.settings.tickets.general.update'), [
                'customer_ticketid' => 'TOOLONG',
                'ticket_character' => 100,
            ])
            ->assertSessionHasErrors('customer_ticketid');
    }

    public function test_update_is_forbidden_for_unauthorized_user(): void
    {
        $this->actingAs($this->unauthorized)
            ->put(route('manager.helpdesk.settings.tickets.general.update'), [
                'customer_ticketid' => 'SPT',
                'ticket_character' => 100,
            ])
            ->assertForbidden();
    }

    public function test_update_does_not_return_json_response(): void
    {
        $response = $this->actingAs($this->manager)
            ->put(route('manager.helpdesk.settings.tickets.general.update'), [
                'customer_ticketid' => 'SPT',
                'ticket_character' => 100,
            ]);

        // Must be a redirect, NOT a JSON response
        $response->assertRedirect();
        $this->assertNotSame('application/json', $response->headers->get('Content-Type'));
    }
}
