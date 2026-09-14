<?php

namespace Modules\HelpdeskTickets\Tests\Feature\Api;

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketGroup;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Tests\Concerns\SharesHelpdeskPdo;
use Tests\Concerns\SeedsHelpdeskRoles;
use Tests\TestCase;

/**
 * Fix de seguridad del 14-sep-2026 (auditoría): show()/update()/index() de
 * Api\TicketsController solo comprobaban el permiso PLANO de entrada
 * (helpdesk.tickets.view/update), nunca contra la instancia vía TicketPolicy
 * — a diferencia de TODO el resto del módulo. Un token con ese permiso
 * genérico podía leer/modificar CUALQUIER ticket del sistema, de cualquier
 * equipo/agente, solo tecleando el ticket_number. Mismo patrón de test que
 * Modules\Helpdesk\Tests\Feature\Api\V1ScopingTest (Conversaciones).
 *
 * Sanctum::actingAs($user, ['*']) — CON abilities explícitas: sin ellas,
 * el middleware EnsureHelpdeskApiScope (WIP de otra sesión, sin comitear
 * a fecha de este test) devuelve 403 antes de llegar al controlador,
 * incluso para el token transitorio de test. Con ['*'] la comprobación de
 * scope se salta y el único límite real en este test es TicketPolicy, que
 * es lo que se quiere probar aquí — no el middleware de scope (ese bug
 * de EnsureHelpdeskApiScope se reportó aparte, no se toca en este commit).
 */
class TicketsControllerScopingTest extends TestCase
{
    use SeedsHelpdeskRoles;
    use SharesHelpdeskPdo;

    private User $agent;

    private TicketStatus $openStatus;

    private Ticket $ownTicket;

    private Ticket $foreignTicket;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedHelpdeskRoles();

        $this->agent = User::factory()->create();
        $this->agent->givePermissionTo(['helpdesk.tickets.view', 'helpdesk.tickets.update']);

        $this->openStatus = TicketStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );

        // "Suyo": asignado directamente al agente — TicketPolicy::view()/
        // update() lo dejan pasar por el atajo de assignee_id, sin importar
        // el equipo.
        $this->ownTicket = Ticket::create([
            'subject' => 'Ticket propio',
            'description' => 'Asignado directamente al agente.',
            'status_id' => $this->openStatus->id,
            'priority' => 'normal',
            'source' => 'web',
            'assignee_id' => $this->agent->id,
        ]);

        // Ajeno: de un equipo al que el agente NO pertenece, ni asignado a
        // él — exactamente el caso que la Policy debe bloquear y que el
        // controlador antes del fix ignoraba por completo.
        $foreignGroup = TicketGroup::create([
            'name' => 'Equipo ajeno '.uniqid(),
            'assignment_mode' => 'manual',
            'is_default' => false,
            'is_active' => true,
        ]);
        $this->foreignTicket = Ticket::create([
            'subject' => 'Ticket ajeno',
            'description' => 'De un equipo al que el agente no pertenece.',
            'status_id' => $this->openStatus->id,
            'priority' => 'normal',
            'source' => 'web',
            'group_id' => $foreignGroup->id,
        ]);
    }

    public function test_show_allows_own_ticket(): void
    {
        Sanctum::actingAs($this->agent, ['*']);

        $this->getJson('/api/v1/helpdesk/tickets/'.$this->ownTicket->ticket_number)
            ->assertOk();
    }

    public function test_show_forbids_foreign_ticket(): void
    {
        Sanctum::actingAs($this->agent, ['*']);

        $this->getJson('/api/v1/helpdesk/tickets/'.$this->foreignTicket->ticket_number)
            ->assertForbidden();
    }

    public function test_update_allows_own_ticket(): void
    {
        Sanctum::actingAs($this->agent, ['*']);

        $this->putJson('/api/v1/helpdesk/tickets/'.$this->ownTicket->ticket_number, [
            'priority' => 'high',
        ])->assertOk();

        $this->assertDatabaseHas('helpdesk_tickets', [
            'id' => $this->ownTicket->id,
            'priority' => 'high',
        ], 'helpdesk');
    }

    public function test_update_forbids_foreign_ticket(): void
    {
        Sanctum::actingAs($this->agent, ['*']);

        $this->putJson('/api/v1/helpdesk/tickets/'.$this->foreignTicket->ticket_number, [
            'priority' => 'high',
        ])->assertForbidden();

        // Y confirma que NO se aplicó de matute antes del check (defensa en
        // profundidad: el controlador ya resuelve el ticket ANTES de
        // autorizar, así que un fallo en el orden de las líneas sí se nota aquí).
        $this->assertDatabaseHas('helpdesk_tickets', [
            'id' => $this->foreignTicket->id,
            'priority' => 'normal',
        ], 'helpdesk');
    }

    public function test_index_only_returns_tickets_in_scope(): void
    {
        Sanctum::actingAs($this->agent, ['*']);

        $response = $this->getJson('/api/v1/helpdesk/tickets?per_page=100')->assertOk();

        $ids = collect($response->json('data.data') ?? $response->json('data'))->pluck('id')->all();

        $this->assertContains($this->ownTicket->id, $ids);
        $this->assertNotContains($this->foreignTicket->id, $ids);
    }

    public function test_index_returns_everything_with_manage_permission(): void
    {
        $this->agent->givePermissionTo('helpdesk.tickets.manage');
        Sanctum::actingAs($this->agent, ['*']);

        $response = $this->getJson('/api/v1/helpdesk/tickets?per_page=100')->assertOk();

        $ids = collect($response->json('data.data') ?? $response->json('data'))->pluck('id')->all();

        $this->assertContains($this->ownTicket->id, $ids);
        $this->assertContains($this->foreignTicket->id, $ids);
    }
}
