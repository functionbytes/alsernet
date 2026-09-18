<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketSlaPolicy;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Tests\TestCase;

/**
 * SLA por canal en el motor real de tickets (helpdesk_ticket_sla_policies):
 * al crear un ticket sin sla_policy_id, TicketObserver::creating resuelve la
 * política vía TicketSlaPolicy::resolveForChannel(tickets.source): primero la
 * política activa con channel coincidente, con fallback a la genérica
 * (is_default, sin canal). Un sla_policy_id explícito siempre gana.
 */
class SlaPolicyChannelResolutionTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    private Customer $customer;

    private TicketStatus $openStatus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->openStatus = TicketStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );

        $this->customer = Customer::firstOrCreate(
            ['email' => 'sla-channel-customer@example.com'],
            ['name' => 'Sla Channel Customer']
        );
    }

    private function makeTicket(array $overrides = []): Ticket
    {
        return Ticket::create(array_merge([
            'subject' => 'Channel SLA test ticket',
            'description' => 'Channel SLA test description',
            'customer_id' => $this->customer->id,
            'status_id' => $this->openStatus->id,
            'priority' => 'normal',
            'source' => 'widget',
        ], $overrides));
    }

    public function test_ticket_gets_the_policy_matching_its_channel(): void
    {
        $generic = TicketSlaPolicy::factory()->default()->create(['name' => 'Genérica']);
        $widget = TicketSlaPolicy::factory()->channel('widget')->create(['name' => 'Widget SLA']);

        $ticket = $this->makeTicket(['source' => 'widget']);

        $this->assertSame($widget->id, $ticket->sla_policy_id, 'La política con canal coincidente debe tener prioridad.');
        $this->assertNotNull($ticket->fresh()->sla_resolution_due_at, 'La política asignada debe calcular vencimientos SLA.');
    }

    public function test_ticket_without_matching_channel_falls_back_to_default_policy(): void
    {
        $generic = TicketSlaPolicy::factory()->default()->create(['name' => 'Genérica']);
        TicketSlaPolicy::factory()->channel('email')->create(['name' => 'Email SLA']);

        $ticket = $this->makeTicket(['source' => 'widget']);

        $this->assertSame($generic->id, $ticket->sla_policy_id, 'Sin política para el canal debe caer a la genérica (is_default).');
    }

    public function test_inactive_channel_policy_is_ignored(): void
    {
        $generic = TicketSlaPolicy::factory()->default()->create(['name' => 'Genérica']);
        TicketSlaPolicy::factory()->channel('widget')->inactive()->create(['name' => 'Widget SLA off']);

        $ticket = $this->makeTicket(['source' => 'widget']);

        $this->assertSame($generic->id, $ticket->sla_policy_id, 'Una política de canal inactiva no debe seleccionarse.');
    }

    public function test_explicit_sla_policy_id_wins_over_channel_resolution(): void
    {
        $explicit = TicketSlaPolicy::factory()->create(['name' => 'Explícita']);
        TicketSlaPolicy::factory()->channel('widget')->create(['name' => 'Widget SLA']);

        $ticket = $this->makeTicket(['source' => 'widget', 'sla_policy_id' => $explicit->id]);

        $this->assertSame($explicit->id, $ticket->sla_policy_id, 'Un sla_policy_id explícito nunca debe sobreescribirse.');
    }

    public function test_ticket_without_any_policy_keeps_null_sla_policy(): void
    {
        // Sin políticas configuradas el resolver es un no-op: comportamiento
        // histórico (ticket sin SLA).
        $ticket = $this->makeTicket(['source' => 'widget']);

        $this->assertNull($ticket->sla_policy_id);
        $this->assertNull($ticket->fresh()->sla_resolution_due_at);
    }

    public function test_resolve_for_channel_without_channel_returns_default(): void
    {
        $generic = TicketSlaPolicy::factory()->default()->create(['name' => 'Genérica']);
        TicketSlaPolicy::factory()->channel('email')->create(['name' => 'Email SLA']);

        $this->assertSame($generic->id, TicketSlaPolicy::resolveForChannel(null)?->id);
        $this->assertSame($generic->id, TicketSlaPolicy::resolveForChannel('')?->id);
    }
}
