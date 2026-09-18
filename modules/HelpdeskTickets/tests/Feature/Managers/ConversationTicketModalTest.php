<?php

namespace Modules\HelpdeskTickets\Tests\Feature\Managers;

use App\Models\User;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Database\Seeders\HelpdeskTicketsPermissionsSeeder;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Tests\Concerns\SharesHelpdeskPdo;
use Tests\Concerns\SeedsHelpdeskRoles;
use Tests\TestCase;

/**
 * Modal de detalle de ticket del inbox: payload y acciones.
 *
 * El modal se rediseñó en sep-2026 y salía entero en blanco por un selector
 * muerto en el JS. Al arreglarlo aparecieron dos cosas más: el payload no
 * traía la mitad de lo que la ficha promete (SLA, último movimiento,
 * etiquetas, pedido, origen legible, actividad real) y el botón "Resolver
 * ticket" no tenía endpoint detrás.
 *
 * Estos tests fijan el contrato del payload y, sobre todo, el cerrojo del
 * endpoint de acciones: el id del ticket llega por la URL, así que sin la
 * comprobación de pertenencia se podría resolver CUALQUIER ticket teniendo
 * acceso a una sola conversación.
 */
class ConversationTicketModalTest extends TestCase
{
    use SeedsHelpdeskRoles;
    use SharesHelpdeskPdo;

    private User $agent;

    private Customer $customer;

    private Conversation $conversation;

    private TicketStatus $openStatus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedHelpdeskRoles();
        $this->seed(HelpdeskTicketsPermissionsSeeder::class);

        $this->agent = User::factory()->create();
        $this->agent->assignRole('super-settings');
        $this->agent->givePermissionTo([
            'helpdesk.tickets.view',
            'helpdesk.tickets.update',
            'helpdesk.tickets.close',
        ]);

        $this->openStatus = TicketStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );

        $this->customer = Customer::firstOrCreate(
            ['email' => 'modal-customer@example.com'],
            ['name' => 'Modal Customer']
        );

        $this->conversation = Conversation::create([
            'customer_id' => $this->customer->id,
            'subject' => 'Conversación de prueba',
            'channel' => 'email',
        ]);
    }

    public function test_el_detalle_trae_todo_lo_que_la_ficha_promete(): void
    {
        $ticket = $this->createTicket([
            'tags' => ['garantia', 'vip'],
            'custom_fields' => ['pedido' => '829575'],
            'source' => 'conversation',
        ]);

        $payload = $this->actingAs($this->agent)
            ->getJson(route('manager.helpdesk.conversations.ticket-detail', [
                'conversation' => $this->conversation->id,
                'ticket' => $ticket->id,
            ]))
            ->assertOk()
            ->json('ticket');

        // Las claves que el modal rellena y que antes no existían.
        foreach (['tags', 'order_ref', 'sla', 'last_move', 'conversation', 'activity', 'source_label'] as $key) {
            $this->assertArrayHasKey($key, $payload, "El payload debe traer '{$key}'.");
        }

        $this->assertSame(['garantia', 'vip'], $payload['tags']);
        $this->assertSame('829575', $payload['order_ref'], 'El pedido sale de custom_fields.');
        $this->assertSame('Conversación', $payload['source_label'], 'El origen se traduce, no se enseña el slug.');
        $this->assertSame($this->conversation->id, $payload['conversation']['id']);
    }

    public function test_el_agente_asignado_sale_con_su_nombre(): void
    {
        // Bug real: 'assignee' => $ticket->assignee?->name devolvía siempre
        // null —el usuario del helpdesk no tiene columna name— así que la
        // ficha decía "Sin asignar" hasta en los tickets asignados.
        $ticket = $this->createTicket(['assignee_id' => $this->agent->id]);

        $payload = $this->actingAs($this->agent)
            ->getJson(route('manager.helpdesk.conversations.ticket-detail', [
                'conversation' => $this->conversation->id,
                'ticket' => $ticket->id,
            ]))
            ->assertOk()
            ->json('ticket');

        $this->assertNotNull($payload['assignee']);
        $this->assertNotSame('Sin asignar', $payload['assignee']);
    }

    public function test_asignarse_el_ticket_desde_el_modal(): void
    {
        $ticket = $this->createTicket();

        $this->actingAs($this->agent)
            ->postJson(route('manager.helpdesk.conversations.ticket-action', [
                'conversation' => $this->conversation->id,
                'ticket' => $ticket->id,
            ]), ['action' => 'assign_me'])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame($this->agent->id, $ticket->fresh()->assignee_id);
    }

    public function test_resolver_el_ticket_desde_el_modal(): void
    {
        $ticket = $this->createTicket();

        $this->actingAs($this->agent)
            ->postJson(route('manager.helpdesk.conversations.ticket-action', [
                'conversation' => $this->conversation->id,
                'ticket' => $ticket->id,
            ]), ['action' => 'resolve'])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertNotNull($ticket->fresh()->resolved_at ?? $ticket->fresh()->closed_at);
    }

    public function test_no_se_puede_tocar_un_ticket_de_otra_conversacion(): void
    {
        // El cerrojo que impide resolver tickets ajenos iterando ids.
        $otraConversacion = Conversation::create([
            'customer_id' => Customer::firstOrCreate(
                ['email' => 'otro-cliente@example.com'],
                ['name' => 'Otro Cliente']
            )->id,
            'subject' => 'Otra conversación',
            'channel' => 'email',
        ]);

        $ajeno = Ticket::create([
            'subject' => 'Ticket de otra conversación',
            'description' => 'No se toca.',
            'customer_id' => $otraConversacion->customer_id,
            'conversation_id' => $otraConversacion->id,
            'status_id' => $this->openStatus->id,
            'priority' => 'normal',
            'source' => 'conversation',
        ]);

        $this->actingAs($this->agent)
            ->postJson(route('manager.helpdesk.conversations.ticket-action', [
                'conversation' => $this->conversation->id,
                'ticket' => $ajeno->id,
            ]), ['action' => 'resolve'])
            ->assertNotFound();

        $this->assertNull($ajeno->fresh()->closed_at);
    }

    public function test_la_accion_se_valida(): void
    {
        $ticket = $this->createTicket();

        $this->actingAs($this->agent)
            ->postJson(route('manager.helpdesk.conversations.ticket-action', [
                'conversation' => $this->conversation->id,
                'ticket' => $ticket->id,
            ]), ['action' => 'borrar_todo'])
            ->assertStatus(422);
    }

    public function test_el_fragmento_del_panel_pinta_las_tarjetas(): void
    {
        $this->createTicket(['subject' => 'Pregunta sobre garantía']);

        $html = $this->actingAs($this->agent)
            ->get(route('manager.helpdesk.conversations.right-panel.tickets', [
                'conversation' => $this->conversation->id,
            ]))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-bv-tab-content="tickets"', $html);
        $this->assertStringContainsString('tk-card', $html);
        // El badge que se solapaba con el chip de estado ya no se pinta.
        $this->assertStringNotContainsString('De esta conversación</span>', $html);
    }

    private function createTicket(array $overrides = []): Ticket
    {
        return Ticket::create(array_merge([
            'subject' => 'Ticket del modal',
            'description' => 'Descripción del ticket.',
            'customer_id' => $this->customer->id,
            'conversation_id' => $this->conversation->id,
            'status_id' => $this->openStatus->id,
            'priority' => 'normal',
            'source' => 'conversation',
        ], $overrides));
    }
}
