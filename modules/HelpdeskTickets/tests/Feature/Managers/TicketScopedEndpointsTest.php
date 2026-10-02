<?php

namespace Modules\HelpdeskTickets\Tests\Feature\Managers;

use App\Models\User;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Database\Seeders\HelpdeskTicketsPermissionsSeeder;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketGroup;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Tests\Concerns\SharesHelpdeskPdo;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\Concerns\SeedsHelpdeskRoles;
use Tests\TestCase;

/**
 * Presencia, artículos sugeridos y traducción solo comprobaban
 * helpdesk.tickets.view, no TicketPolicy::view (acotado por equipo).
 */
class TicketScopedEndpointsTest extends TestCase
{
    use SeedsHelpdeskRoles;
    use SharesHelpdeskPdo;

    private Ticket $foreignTicket;

    private User $agent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedHelpdeskRoles();
        $this->seed(HelpdeskTicketsPermissionsSeeder::class);

        $this->agent = User::factory()->create();
        $this->agent->assignRole('super-settings');

        $group = TicketGroup::create([
            'name' => 'Equipo ajeno '.uniqid(),
            'assignment_mode' => 'manual',
            'is_default' => false,
            'is_active' => true,
        ]);

        $status = TicketStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );

        $customer = Customer::firstOrCreate(
            ['email' => 'scoped-endpoints@example.com'],
            ['name' => 'Scoped Endpoints Customer']
        );

        $this->foreignTicket = Ticket::create([
            'subject' => 'Ticket de otro equipo',
            'description' => 'Descripcion',
            'customer_id' => $customer->id,
            'status_id' => $status->id,
            'priority' => 'normal',
            'source' => 'web',
            'group_id' => $group->id,
        ]);
    }

    /**
     * El seeder da helpdesk.tickets.manage a super-settings, que se salta el
     * acotado por equipo; se retira para que el agente quede acotado.
     */
    private function withoutManagePermission(callable $callback): void
    {
        $role = Role::findByName('super-settings', 'web');
        $role->revokePermissionTo('helpdesk.tickets.manage');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        try {
            $callback($this->agent->fresh());
        } finally {
            $role->givePermissionTo('helpdesk.tickets.manage');
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }

    public function test_presence_heartbeat_is_forbidden_out_of_scope(): void
    {
        $this->withoutManagePermission(fn (User $agent) => $this->actingAs($agent)
            ->postJson(route('manager.helpdesk.tickets.presence.heartbeat', $this->foreignTicket))
            ->assertForbidden());
    }

    public function test_presence_leave_is_forbidden_out_of_scope(): void
    {
        $this->withoutManagePermission(fn (User $agent) => $this->actingAs($agent)
            ->deleteJson(route('manager.helpdesk.tickets.presence.leave', $this->foreignTicket))
            ->assertForbidden());
    }

    public function test_suggested_articles_is_forbidden_out_of_scope(): void
    {
        $this->withoutManagePermission(fn (User $agent) => $this->actingAs($agent)
            ->getJson(route('manager.helpdesk.tickets.suggested-articles', $this->foreignTicket))
            ->assertForbidden());
    }

    public function test_translate_is_forbidden_out_of_scope(): void
    {
        $this->withoutManagePermission(fn (User $agent) => $this->actingAs($agent)
            ->postJson(route('manager.helpdesk.tickets.translate', $this->foreignTicket), [
                'text' => 'Hola',
                'target_lang' => 'en',
            ])
            ->assertForbidden());
    }

    public function test_manager_can_still_use_presence_and_suggestions(): void
    {
        $this->actingAs($this->agent)
            ->postJson(route('manager.helpdesk.tickets.presence.heartbeat', $this->foreignTicket))
            ->assertOk();

        $this->actingAs($this->agent)
            ->getJson(route('manager.helpdesk.tickets.suggested-articles', $this->foreignTicket))
            ->assertOk();
    }
}
