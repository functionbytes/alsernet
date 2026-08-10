<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class BulkTicketOperationsTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    private User $manager;

    private TicketStatus $status;

    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->helpdeskConnectionAvailable()) {
            $this->markTestSkipped('Helpdesk database connection is not available.');
        }

        foreach (['helpdesk.tickets.view', 'helpdesk.tickets.update', 'helpdesk.tickets.delete'] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Las rutas manager exigen role:super-admin|super-settings, pero ambos
        // roles pasan por alto TODAS las policies vía Gate::before hooks (ver
        // AuthServiceProvider/DocumentsServiceProvider), lo que haría
        // intestable la autorización por-ticket. Igual que BulkReplyTest:
        // se desactiva el middleware de rol y se prueba la capa
        // controller/policy con usuarios que solo tienen los permisos
        // explícitamente concedidos.
        $this->withoutMiddleware(RoleMiddleware::class);

        $this->manager = User::factory()->create();
        $this->manager->givePermissionTo(['helpdesk.tickets.view', 'helpdesk.tickets.update', 'helpdesk.tickets.delete']);

        $this->status = TicketStatus::firstOrCreate(
            ['slug' => 'open'],
            [
                'name' => 'Open',
                'color' => '#13C672',
                'is_open' => true,
                'is_default' => true,
                'order' => 1,
            ]
        );
    }

    public function test_bulk_close_requires_authentication(): void
    {
        $ticket = $this->createTestTicket();

        // El endpoint solo se consume por AJAX (JSON), así que un usuario no
        // autenticado recibe 401 en vez de un redirect a /login.
        $this->postJson(route('manager.helpdesk.tickets.bulk'), [
            'ticket_ids' => [$ticket->id],
            'action' => 'close',
        ])->assertUnauthorized();
    }

    public function test_bulk_close_marks_tickets_as_closed(): void
    {
        $ticketA = $this->createTestTicket(['subject' => 'Ticket A']);
        $ticketB = $this->createTestTicket(['subject' => 'Ticket B']);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.bulk'), [
                'ticket_ids' => [$ticketA->id, $ticketB->id],
                'action' => 'close',
            ])->assertOk()->assertJson(['success' => true, 'updated_count' => 2]);

        $this->assertNotNull(Ticket::find($ticketA->id)->closed_at);
        $this->assertNotNull(Ticket::find($ticketB->id)->closed_at);
    }

    /**
     * Reproduce el payload real que envía la bulk-bar (tickets.js::bulkAction)
     * al pulsar "Resolver". Antes de este fix, `resolve` no estaba en la lista
     * blanca de BulkTicketRequest ni en el match() del controller: 422 siempre.
     */
    public function test_bulk_resolve_marks_tickets_as_resolved(): void
    {
        $ticket = $this->createTestTicket();

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.bulk'), [
                'ticket_ids' => [$ticket->id],
                'action' => 'resolve',
            ])->assertOk()->assertJson(['success' => true, 'updated_count' => 1]);

        $this->assertNotNull(Ticket::find($ticket->id)->resolved_at);
    }

    /**
     * Bug real encontrado en QA de "Gestión de tickets" (ago-2026):
     * Ticket::resolve() marcaba resolved_at pero nunca cambiaba status_id, a
     * diferencia de close()/reopen(). El ticket seguía apareciendo "Abierto"
     * en cualquier listado (el estado visible se deriva de status_id). El
     * test anterior (test_bulk_resolve_marks_tickets_as_resolved) no lo
     * detectó porque solo comprobaba resolved_at, nunca el status.
     */
    public function test_bulk_resolve_updates_the_ticket_status_to_resolved(): void
    {
        $resolvedStatus = TicketStatus::firstOrCreate(
            ['slug' => 'resolved'],
            ['name' => 'Resuelto', 'color' => '#16a34a', 'is_open' => false, 'order' => 4]
        );
        $ticket = $this->createTestTicket();

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.bulk'), [
                'ticket_ids' => [$ticket->id],
                'action' => 'resolve',
            ])->assertOk()->assertJson(['success' => true, 'updated_count' => 1]);

        $this->assertSame($resolvedStatus->id, Ticket::find($ticket->id)->status_id);
    }

    public function test_bulk_add_tag_appends_tag_to_all_tickets(): void
    {
        $ticketA = $this->createTestTicket();
        $ticketB = $this->createTestTicket(['tags' => ['existente']]);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.bulk'), [
                'ticket_ids' => [$ticketA->id, $ticketB->id],
                'action' => 'add_tag',
                'tag' => 'urgente-triaje',
            ])->assertOk()->assertJson(['success' => true, 'updated_count' => 2]);

        $this->assertContains('urgente-triaje', Ticket::find($ticketA->id)->tags);
        // No duplica ni pierde las etiquetas previas.
        $this->assertEqualsCanonicalizing(['existente', 'urgente-triaje'], Ticket::find($ticketB->id)->tags);
    }

    public function test_bulk_add_tag_requires_the_tag(): void
    {
        $ticket = $this->createTestTicket();

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.bulk'), [
                'ticket_ids' => [$ticket->id],
                'action' => 'add_tag',
            ])->assertJsonValidationErrors(['tag']);
    }

    public function test_bulk_assign_sets_assigned_to_on_all_tickets(): void
    {
        $agent = User::factory()->create();
        $ticketA = $this->createTestTicket();
        $ticketB = $this->createTestTicket();

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.bulk'), [
                'ticket_ids' => [$ticketA->id, $ticketB->id],
                'action' => 'assign',
                'agent_id' => $agent->id,
            ])->assertOk()->assertJson(['success' => true, 'updated_count' => 2]);

        $this->assertEquals($agent->id, Ticket::find($ticketA->id)->assignee_id);
        $this->assertEquals($agent->id, Ticket::find($ticketB->id)->assignee_id);
    }

    public function test_bulk_delete_soft_deletes_tickets(): void
    {
        $ticket = $this->createTestTicket(['subject' => 'To be deleted']);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.bulk'), [
                'ticket_ids' => [$ticket->id],
                'action' => 'delete',
            ])->assertOk()->assertJson(['success' => true, 'updated_count' => 1]);

        $this->assertSoftDeleted('helpdesk_tickets', ['id' => $ticket->id], 'helpdesk');
    }

    public function test_bulk_reopen_clears_closed_at(): void
    {
        $ticket = $this->createTestTicket(['closed_at' => now()]);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.bulk'), [
                'ticket_ids' => [$ticket->id],
                'action' => 'reopen',
            ])->assertOk()->assertJson(['success' => true, 'updated_count' => 1]);

        $this->assertNull(Ticket::find($ticket->id)->closed_at);
    }

    /**
     * Autorización granular por ticket (igual que TicketMessagingController::
     * bulkReply, ver BulkReplyTest): un ticket sobre el que el usuario no
     * tiene permiso se omite en vez de abortar toda la operación, y se
     * reporta en skipped_ticket_ids.
     */
    public function test_bulk_close_skips_tickets_the_user_cannot_act_on(): void
    {
        // Solo helpdesk.tickets.view: TicketPolicy::close() solo le deja
        // actuar sobre el ticket del que es assignee.
        $limitedAgent = User::factory()->create();
        $limitedAgent->givePermissionTo('helpdesk.tickets.view');

        $ownTicket = $this->createTestTicket(['assignee_id' => $limitedAgent->id]);
        $foreignTicket = $this->createTestTicket();

        $response = $this->actingAs($limitedAgent)
            ->postJson(route('manager.helpdesk.tickets.bulk'), [
                'ticket_ids' => [$ownTicket->id, $foreignTicket->id],
                'action' => 'close',
            ])->assertOk();

        $response->assertJson(['success' => true, 'updated_count' => 1]);
        $response->assertJsonPath('skipped_ticket_ids.0', $foreignTicket->id);

        $this->assertNotNull(Ticket::find($ownTicket->id)->closed_at);
        $this->assertNull(Ticket::find($foreignTicket->id)->closed_at);
    }

    /**
     * Sin ningún ticket autorizado, la operación completa se rechaza (403) en
     * vez de devolver un falso "0 actualizados" con success:true.
     */
    public function test_bulk_close_returns_403_when_no_ticket_is_authorized(): void
    {
        $limitedAgent = User::factory()->create();
        $limitedAgent->givePermissionTo('helpdesk.tickets.view');

        $foreignTicket = $this->createTestTicket();

        $this->actingAs($limitedAgent)
            ->postJson(route('manager.helpdesk.tickets.bulk'), [
                'ticket_ids' => [$foreignTicket->id],
                'action' => 'close',
            ])
            ->assertForbidden()
            ->assertJsonPath('skipped_ticket_ids.0', $foreignTicket->id);

        $this->assertNull(Ticket::find($foreignTicket->id)->closed_at);
    }

    public function test_validation_fails_when_ticket_ids_is_empty(): void
    {
        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.bulk'), [
                'ticket_ids' => [],
                'action' => 'close',
            ])->assertJsonValidationErrors(['ticket_ids']);
    }

    public function test_validation_fails_when_action_is_invalid(): void
    {
        $ticket = $this->createTestTicket();

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.bulk'), [
                'ticket_ids' => [$ticket->id],
                'action' => 'explode',
            ])->assertJsonValidationErrors(['action']);
    }

    public function test_validation_fails_when_more_than_100_tickets_selected(): void
    {
        $ids = range(1, 101);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.bulk'), [
                'ticket_ids' => $ids,
                'action' => 'close',
            ])->assertJsonValidationErrors(['ticket_ids']);
    }

    public function test_bulk_assign_requires_agent_id(): void
    {
        $ticket = $this->createTestTicket();

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.bulk'), [
                'ticket_ids' => [$ticket->id],
                'action' => 'assign',
                // agent_id intentionally omitted
            ])->assertJsonValidationErrors(['agent_id']);
    }

    public function test_validation_fails_when_ticket_ids_missing_entirely(): void
    {
        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.bulk'), [
                'action' => 'close',
            ])->assertJsonValidationErrors(['ticket_ids']);
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createTestTicket(array $overrides = []): Ticket
    {
        return Ticket::create(array_merge([
            'subject' => 'Test ticket',
            'description' => 'Test description.',
            'status_id' => $this->status->id,
            'priority' => 'normal',
            'source' => 'web',
        ], $overrides));
    }

    private function helpdeskConnectionAvailable(): bool
    {
        try {
            DB::connection('helpdesk')->getPdo();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
