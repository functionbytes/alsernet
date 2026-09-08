<?php

namespace Modules\HelpdeskTickets\Tests\Feature\Policies;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Database\Seeders\HelpdeskTicketsPermissionsSeeder;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketGroup;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Tests\TestCase;

class TicketPolicyTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private Customer $customer;

    private TicketStatus $openStatus;

    protected function setUp(): void
    {
        parent::setUp();

        // TicketPolicy usa hasPermissionTo(), que LANZA PermissionDoesNotExist
        // si el permiso no está en BD — la BD de test arranca sin permisos.
        $this->seed(HelpdeskTicketsPermissionsSeeder::class);

        $this->openStatus = TicketStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );

        $this->customer = Customer::firstOrCreate(
            ['email' => 'policy-test-customer@example.com'],
            ['name' => 'Policy Test Customer']
        );
    }

    public function test_user_without_permission_cannot_view_any(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($user->can('viewAny', Ticket::class));
    }

    public function test_user_with_view_permission_can_view_any(): void
    {
        $user = User::factory()->create();

        try {
            $user->givePermissionTo('helpdesk.tickets.view');
        } catch (\Throwable) {
            $this->markTestSkipped('Permissions not available in test env.');
        }

        $this->assertTrue($user->can('viewAny', Ticket::class));
    }

    public function test_user_without_create_permission_cannot_create(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($user->can('create', Ticket::class));
    }

    public function test_user_with_create_permission_can_create(): void
    {
        $user = User::factory()->create();

        try {
            $user->givePermissionTo('helpdesk.tickets.create');
        } catch (\Throwable) {
            $this->markTestSkipped('Permissions not available in test env.');
        }

        $this->assertTrue($user->can('create', Ticket::class));
    }

    public function test_assignee_can_view_own_ticket_without_global_permission(): void
    {
        $assignee = User::factory()->create();
        $ticket = $this->createTicket(['assignee_id' => $assignee->id]);

        $this->assertTrue($assignee->can('view', $ticket));
    }

    public function test_non_assignee_without_permission_cannot_view_ticket(): void
    {
        $otherUser = User::factory()->create();
        $ticket = $this->createTicket(['assignee_id' => null]);

        $this->assertFalse($otherUser->can('view', $ticket));
    }

    public function test_only_super_admin_can_force_delete(): void
    {
        $regularUser = User::factory()->create();
        $ticket = $this->createTicket();

        $this->assertFalse($regularUser->can('forceDelete', $ticket));
    }

    public function test_super_admin_can_force_delete(): void
    {
        $superAdmin = User::factory()->create();

        try {
            $superAdmin->assignRole('super-admin');
        } catch (\Throwable) {
            $this->markTestSkipped('Roles not available in test env.');
        }

        $ticket = $this->createTicket();

        $this->assertTrue($superAdmin->can('forceDelete', $ticket));
    }

    public function test_assign_requires_update_permission(): void
    {
        $user = User::factory()->create();
        $ticket = $this->createTicket();

        $this->assertFalse($user->can('assign', $ticket));
    }

    /**
     * helpdesk.tickets.update a secas ya NO basta para CUALQUIER ticket
     * (8-sep-2026): TicketPolicy::inScope() acota a helpdesk.tickets.manage
     * o a un equipo del que el usuario forme parte — el mismo permiso base
     * que hace falta para trabajar en el propio listado abría antes
     * cualquier ticket de cualquier equipo. Este test comprobaba justo ese
     * comportamiento viejo con un ticket sin equipo (createTicket() no le
     * pone group_id); se actualiza metiendo al usuario y al ticket en el
     * mismo equipo, que es el caso real que la Policy sí debe dejar pasar.
     */
    public function test_user_with_update_permission_can_assign(): void
    {
        $user = User::factory()->create();

        try {
            $user->givePermissionTo('helpdesk.tickets.update');
        } catch (\Throwable) {
            $this->markTestSkipped('Permissions not available in test env.');
        }

        // TicketGroup no tiene HasFactory enganchado (TicketGroupFactory
        // existe pero el modelo no la referencia) — create() directo.
        $group = TicketGroup::create([
            'name' => 'Equipo de prueba '.uniqid(),
            'assignment_mode' => 'manual',
            'is_default' => false,
            'is_active' => true,
        ]);
        DB::connection('helpdesk')->table('helpdesk_group_user')->insert([
            'group_id' => $group->id,
            'user_id' => $user->id,
            'conversation_priority' => 'primary',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $ticket = $this->createTicket(['group_id' => $group->id]);

        $this->assertTrue($user->can('assign', $ticket));
    }

    /**
     * helpdesk.tickets.manage sí ve/actúa sobre cualquier ticket, sin
     * importar el equipo — es el permiso de quien administra el módulo
     * completo (super-admin, super-settings, helpdesk-admin).
     */
    public function test_user_with_manage_permission_can_assign_any_ticket(): void
    {
        $user = User::factory()->create();

        try {
            $user->givePermissionTo(['helpdesk.tickets.update', 'helpdesk.tickets.manage']);
        } catch (\Throwable) {
            $this->markTestSkipped('Permissions not available in test env.');
        }

        // Sin equipo y sin que el usuario pertenezca a ninguno: manage se
        // salta el acotado por equipo por completo.
        $ticket = $this->createTicket();

        $this->assertTrue($user->can('assign', $ticket));
    }

    /**
     * El mismo permiso base, pero SIN pertenecer al equipo del ticket ni ser
     * el asignado: la IDOR real que este acotado por equipo cierra — antes
     * cualquier agente con helpdesk.tickets.update podía asignarse (o
     * asignar a otro) un ticket de un equipo ajeno tecleando la URL.
     */
    public function test_user_with_update_permission_cannot_assign_ticket_of_another_team(): void
    {
        $user = User::factory()->create();

        try {
            $user->givePermissionTo('helpdesk.tickets.update');
        } catch (\Throwable) {
            $this->markTestSkipped('Permissions not available in test env.');
        }

        $foreignGroup = TicketGroup::create([
            'name' => 'Equipo ajeno '.uniqid(),
            'assignment_mode' => 'manual',
            'is_default' => false,
            'is_active' => true,
        ]);
        $ticket = $this->createTicket(['group_id' => $foreignGroup->id]);

        $this->assertFalse($user->can('assign', $ticket));
    }

    /**
     * Un ticket SIN equipo (group_id null) es bote compartido: cualquiera con
     * el permiso base puede verlo/asignárselo, no solo quien tenga
     * helpdesk.tickets.manage. 8-sep-2026: TicketsCrudController::
     * scopeToVisibleTickets() ya lo enseñaba en el listado; sin este mismo
     * caso en inScope(), actuar sobre él (asignar, cerrar, actualizar) daba
     * 403 pese a que el agente lo veía en su propia lista.
     */
    public function test_user_with_view_permission_can_view_ticket_without_team(): void
    {
        $user = User::factory()->create();

        try {
            $user->givePermissionTo('helpdesk.tickets.view');
        } catch (\Throwable) {
            $this->markTestSkipped('Permissions not available in test env.');
        }

        $ticket = $this->createTicket(); // sin group_id, sin assignee_id

        $this->assertTrue($user->can('view', $ticket));
    }

    public function test_user_with_update_permission_can_assign_ticket_without_team(): void
    {
        $user = User::factory()->create();

        try {
            $user->givePermissionTo('helpdesk.tickets.update');
        } catch (\Throwable) {
            $this->markTestSkipped('Permissions not available in test env.');
        }

        $ticket = $this->createTicket(); // sin group_id

        $this->assertTrue($user->can('assign', $ticket));
    }

    public function test_assignee_can_update_own_ticket_without_global_permission(): void
    {
        $assignee = User::factory()->create();
        $ticket = $this->createTicket(['assignee_id' => $assignee->id]);

        $this->assertTrue($assignee->can('update', $ticket));
    }

    public function test_non_assignee_without_permission_cannot_update(): void
    {
        $otherUser = User::factory()->create();
        $ticket = $this->createTicket(['assignee_id' => null]);

        $this->assertFalse($otherUser->can('update', $ticket));
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function createTicket(array $overrides = []): Ticket
    {
        return Ticket::create(array_merge([
            'subject' => 'Policy test ticket',
            'description' => 'Test description.',
            'customer_id' => $this->customer->id,
            'status_id' => $this->openStatus->id,
            'priority' => 'normal',
            'source' => 'web',
        ], $overrides));
    }
}
