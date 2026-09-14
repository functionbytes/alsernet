<?php

namespace Modules\HelpdeskTickets\Tests\Feature\Jobs;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Events\SlaBreached;
use Modules\HelpdeskTickets\Jobs\CheckSlaBreaches;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Services\SlaService;
use Tests\TestCase;

/**
 * Cubre el barrido de incumplimientos de SLA, que corre cada quince minutos sin
 * que nadie mire su log.
 *
 * El caso que faltaba es el de los tickets con el reloj pausado: el job no
 * filtraba por sla_paused_at, así que un ticket en espera del cliente se
 * marcaba como incumplido y disparaba el correo al responsable, mientras la
 * lista lo seguía pintando en verde porque ahí sí se compensa la pausa
 * (Ticket::slaEffectiveDueDate()).
 */
class CheckSlaBreachesTest extends TestCase
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
            ['email' => 'sla-breach-customer@example.com'],
            ['name' => 'Sla Breach Customer']
        );
    }

    public function test_marks_overdue_ticket_as_breached(): void
    {
        Event::fake([SlaBreached::class]);

        $ticket = $this->makeTicket(['sla_resolution_due_at' => now()->subMinutes(10)]);

        $this->runJob();

        $this->assertTrue((bool) $ticket->fresh()->sla_resolution_breached);
        Event::assertDispatched(SlaBreached::class);
    }

    public function test_does_not_mark_paused_ticket_as_breached(): void
    {
        Event::fake([SlaBreached::class]);

        // Vencimiento nominal pasado, pero el reloj lleva pausado desde antes:
        // el vencimiento efectivo aún no ha llegado.
        $ticket = $this->makeTicket([
            'sla_resolution_due_at' => now()->subMinutes(10),
            'sla_paused_at' => now()->subMinutes(30),
        ]);

        $this->runJob();

        $this->assertFalse((bool) $ticket->fresh()->sla_resolution_breached);
        Event::assertNotDispatched(SlaBreached::class);
    }

    public function test_ignores_tickets_not_yet_due(): void
    {
        Event::fake([SlaBreached::class]);

        $ticket = $this->makeTicket(['sla_resolution_due_at' => now()->addHours(3)]);

        $this->runJob();

        $this->assertFalse((bool) $ticket->fresh()->sla_resolution_breached);
    }

    public function test_ignores_closed_tickets(): void
    {
        Event::fake([SlaBreached::class]);

        $ticket = $this->makeTicket([
            'sla_resolution_due_at' => now()->subHours(5),
            'closed_at' => now()->subHour(),
        ]);

        $this->runJob();

        $this->assertFalse((bool) $ticket->fresh()->sla_resolution_breached);
    }

    public function test_does_not_re_mark_an_already_breached_ticket(): void
    {
        Event::fake([SlaBreached::class]);

        $this->makeTicket([
            'sla_resolution_due_at' => now()->subHours(5),
            'sla_resolution_breached' => true,
        ]);

        $this->runJob();

        Event::assertNotDispatched(SlaBreached::class);
    }

    private function runJob(): void
    {
        (new CheckSlaBreaches)->handle(app(SlaService::class));
    }

    private function makeTicket(array $overrides = []): Ticket
    {
        $ticket = Ticket::create([
            'subject' => 'SLA breach test ticket',
            'description' => 'SLA breach test description',
            'customer_id' => $this->customer->id,
            'status_id' => $this->openStatus->id,
            'priority' => 'normal',
            'source' => 'portal',
        ]);

        // forceFill + saveQuietly: fijar las columnas de SLA directamente, sin
        // que el observer las recalcule por la política del canal.
        $ticket->forceFill(array_merge(['sla_resolution_breached' => false], $overrides))->saveQuietly();

        return $ticket->refresh();
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
