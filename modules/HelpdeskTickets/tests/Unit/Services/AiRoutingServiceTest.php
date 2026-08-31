<?php

namespace Modules\HelpdeskTickets\Tests\Unit\Services;

use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Services\AiRoutingService;
use Modules\HelpdeskTickets\Services\AssignmentService;
use Tests\TestCase;

/**
 * Frenos del enrutado asistido.
 *
 * Un enrutado que se equivoca no lanza ningun error: deja el ticket en la mesa
 * del agente equivocado, donde puede quedarse dias. Por eso lo que se prueba
 * aqui no es que asigne, sino CUANDO se niega a asignar.
 */
class AiRoutingServiceTest extends TestCase
{
    private function service(): AiRoutingService
    {
        // El AssignmentService se mockea entero: si alguna guarda fallara y el
        // servicio intentara asignar, el test lo delataria en vez de tocar la BD.
        $assignments = $this->createMock(AssignmentService::class);
        $assignments->expects($this->never())->method('autoAssignByWorkload');

        return new AiRoutingService($assignments);
    }

    public function test_it_does_nothing_while_the_feature_is_off(): void
    {
        config(['helpdeskagents.ticket_ai.routing.enabled' => false]);

        $result = $this->service()->route(new Ticket);

        $this->assertFalse($result['applied']);
        $this->assertSame('routing_disabled', $result['reason']);
    }

    public function test_it_never_overrides_a_human_assignment(): void
    {
        config(['helpdeskagents.ticket_ai.routing.enabled' => true]);

        $ticket = new Ticket;
        $ticket->assignee_id = 7;

        $result = $this->service()->route($ticket);

        $this->assertFalse($result['applied']);
        $this->assertSame('already_assigned', $result['reason']);
    }
}
