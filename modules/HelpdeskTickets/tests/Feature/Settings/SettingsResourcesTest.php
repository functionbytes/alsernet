<?php

namespace Modules\HelpdeskTickets\Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Tests\Concerns\SeedsHelpdeskRoles;
use Tests\TestCase;

class SettingsResourcesTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsHelpdeskRoles;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'helpdesk.tickets.settings', 'guard_name' => 'web']);

        $this->admin = User::factory()->create();
        $this->admin->givePermissionTo('helpdesk.tickets.settings');

        // Las rutas de settings llevan middleware role:super-admin|super-settings.
        $this->seedHelpdeskRoles();
        $this->admin->assignRole('super-settings');
    }

    // ─── Statuses ─────────────────────────────────────────────────────────────

    public function test_store_status_creates_record(): void
    {
        $this->actingAs($this->admin)
            ->post(route('manager.helpdesk.settings.ticket-statuses.store'), [
                'name' => 'En revision',
                'color' => '#3b82f6',
                'is_open' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('helpdesk_ticket_statuses', [
            'name' => 'En revision',
            'color' => '#3b82f6',
        ], 'helpdesk');
    }

    public function test_store_status_requires_settings_permission(): void
    {
        $unauthorized = User::factory()->create();

        $this->actingAs($unauthorized)
            ->post(route('manager.helpdesk.settings.ticket-statuses.store'), [
                'name' => 'Estado sin permiso',
                'color' => '#000000',
            ])
            ->assertForbidden();
    }

    public function test_store_status_validates_missing_name(): void
    {
        $this->actingAs($this->admin)
            ->post(route('manager.helpdesk.settings.ticket-statuses.store'), [
                'color' => '#000000',
            ])
            ->assertSessionHasErrors(['name']);
    }

    public function test_store_status_validates_invalid_color(): void
    {
        $this->actingAs($this->admin)
            ->post(route('manager.helpdesk.settings.ticket-statuses.store'), [
                'name' => 'Estado invalido',
                'color' => 'not-a-color',
            ])
            ->assertSessionHasErrors(['color']);
    }

    // ─── Categories ───────────────────────────────────────────────────────────

    public function test_store_category_creates_record(): void
    {
        $this->actingAs($this->admin)
            ->post(route('manager.helpdesk.settings.ticket-categories.store'), [
                'name' => 'Soporte Tecnico',
                'active' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('helpdesk_ticket_categories', [
            'name' => 'Soporte Tecnico',
        ], 'helpdesk');
    }

    public function test_store_category_requires_settings_permission(): void
    {
        $unauthorized = User::factory()->create();

        $this->actingAs($unauthorized)
            ->post(route('manager.helpdesk.settings.ticket-categories.store'), [
                'name' => 'Categoria sin permiso',
            ])
            ->assertForbidden();
    }

    public function test_store_category_validates_missing_name(): void
    {
        $this->actingAs($this->admin)
            ->post(route('manager.helpdesk.settings.ticket-categories.store'), [])
            ->assertSessionHasErrors(['name']);
    }

    // ─── Groups ───────────────────────────────────────────────────────────────

    public function test_store_group_creates_record(): void
    {
        $this->actingAs($this->admin)
            ->post(route('manager.helpdesk.settings.ticket-groups.store'), [
                'name' => 'Equipo de Soporte',
                'assignment_mode' => 'manual',
                'is_active' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('helpdesk_ticket_groups', [
            'name' => 'Equipo de Soporte',
            'assignment_mode' => 'manual',
        ], 'helpdesk');
    }

    public function test_store_group_requires_settings_permission(): void
    {
        $unauthorized = User::factory()->create();

        $this->actingAs($unauthorized)
            ->post(route('manager.helpdesk.settings.ticket-groups.store'), [
                'name' => 'Grupo sin permiso',
                'assignment_mode' => 'manual',
            ])
            ->assertForbidden();
    }

    public function test_store_group_validates_invalid_assignment_mode(): void
    {
        $this->actingAs($this->admin)
            ->post(route('manager.helpdesk.settings.ticket-groups.store'), [
                'name' => 'Grupo invalido',
                'assignment_mode' => 'telepathic',
            ])
            ->assertSessionHasErrors(['assignment_mode']);
    }

    // ─── SLA Policies ─────────────────────────────────────────────────────────

    public function test_store_sla_policy_creates_record(): void
    {
        $this->actingAs($this->admin)
            ->post(route('manager.helpdesk.settings.ticket-sla-policies.store'), [
                'name' => 'SLA Estandar',
                'first_response_time' => 60,
                'resolution_time' => 480,
                'timezone' => 'America/Bogota',
            ])
            ->assertRedirect();

        // El store de ticket-sla-policies escribe en helpdesk_ticket_sla_policies
        // (modelo TicketSlaPolicy), no en la tabla core helpdesk_sla_policies.
        $this->assertDatabaseHas('helpdesk_ticket_sla_policies', [
            'name' => 'SLA Estandar',
            'first_response_time' => 60,
        ], 'helpdesk');
    }

    public function test_store_sla_policy_requires_settings_permission(): void
    {
        $unauthorized = User::factory()->create();

        $this->actingAs($unauthorized)
            ->post(route('manager.helpdesk.settings.ticket-sla-policies.store'), [
                'name' => 'SLA sin permiso',
                'first_response_time' => 60,
                'resolution_time' => 480,
                'timezone' => 'America/Bogota',
            ])
            ->assertForbidden();
    }

    public function test_store_sla_policy_validates_missing_name(): void
    {
        $this->actingAs($this->admin)
            ->post(route('manager.helpdesk.settings.ticket-sla-policies.store'), [
                'first_response_time' => 60,
                'resolution_time' => 480,
                'timezone' => 'America/Bogota',
            ])
            ->assertSessionHasErrors(['name']);
    }

    public function test_store_sla_policy_validates_invalid_timezone(): void
    {
        $this->actingAs($this->admin)
            ->post(route('manager.helpdesk.settings.ticket-sla-policies.store'), [
                'name' => 'SLA invalido',
                'first_response_time' => 60,
                'resolution_time' => 480,
                'timezone' => 'InvalidTimezone/Fake',
            ])
            ->assertSessionHasErrors(['timezone']);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

}
