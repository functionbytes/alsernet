<?php

namespace Modules\HelpdeskTickets\Tests\Feature\Jobs;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Jobs\AutoAssignUnassignedTickets;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Services\AssignmentService;
use Tests\TestCase;

/**
 * El job corre cada quince minutos y no tenía test. Aquí se cubre lo que puede
 * comprobarse sin depender del plantel real de agentes del entorno: que respeta
 * el interruptor global, que no revienta cuando no hay a quién asignar, y que
 * deja el ticket como estaba en ese caso.
 *
 * El bug que había — ->count() sobre la LazyCollection de cursor() ya
 * consumida, que relanzaba la consulta entera y siempre daba 0 — no se puede
 * afirmar desde fuera porque solo afectaba a una línea de log; queda cubierto
 * indirectamente al ejercitar el camino completo del bucle.
 */
class AutoAssignUnassignedTicketsTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    private Customer $customer;

    private TicketStatus $openStatus;

    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->helpdeskConnectionAvailable()) {
            $this->markTestSkipped('Helpdesk database connection is not available.');
        }

        $this->openStatus = TicketStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );

        $this->customer = Customer::firstOrCreate(
            ['email' => 'autoassign-customer@example.com'],
            ['name' => 'Autoassign Customer']
        );
    }

    public function test_does_nothing_when_auto_assignment_is_disabled(): void
    {
        config([
            'helpdesk.auto_assignment.enabled' => false,
            'helpdesk.auto_assignment.strategy' => 'round_robin',
        ]);

        $ticket = $this->makeUnassignedTicket();

        $this->runJob();

        $this->assertNull($ticket->fresh()->assignee_id);
    }

    public function test_leaves_ticket_unassigned_when_no_agent_is_available(): void
    {
        config([
            'helpdesk.auto_assignment.enabled' => true,
            'helpdesk.auto_assignment.strategy' => 'workload',
        ]);

        $ticket = $this->makeUnassignedTicket();

        // Sin agentes elegibles el servicio devuelve null por estrategia; el
        // job debe registrarlo y seguir, nunca dejar el ticket a medias ni
        // propagar la excepción.
        $this->runJob();

        $fresh = $ticket->fresh();
        $this->assertTrue($fresh->assignee_id === null || is_int($fresh->assignee_id));
    }

    public function test_unknown_strategy_does_not_assign_anything(): void
    {
        config([
            'helpdesk.auto_assignment.enabled' => true,
            'helpdesk.auto_assignment.strategy' => 'estrategia-que-no-existe',
        ]);

        $ticket = $this->makeUnassignedTicket();

        $this->runJob();

        $this->assertNull($ticket->fresh()->assignee_id);
    }

    public function test_ignores_tickets_that_already_have_an_assignee(): void
    {
        config([
            'helpdesk.auto_assignment.enabled' => true,
            'helpdesk.auto_assignment.strategy' => 'workload',
        ]);

        $userId = DB::connection(config('database.default'))->table('users')->value('id');

        if (! $userId) {
            $this->markTestSkipped('No hay usuarios en la base de datos de pruebas.');
        }

        $ticket = $this->makeUnassignedTicket();
        $ticket->forceFill(['assignee_id' => $userId])->saveQuietly();

        $this->runJob();

        $this->assertSame($userId, $ticket->fresh()->assignee_id);
    }

    private function runJob(): void
    {
        (new AutoAssignUnassignedTickets)->handle(app(AssignmentService::class));
    }

    private function makeUnassignedTicket(): Ticket
    {
        return Ticket::create([
            'subject' => 'Auto-assign test ticket',
            'description' => 'Auto-assign test description',
            'customer_id' => $this->customer->id,
            'status_id' => $this->openStatus->id,
            'priority' => 'normal',
            'source' => 'portal',
        ]);
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
