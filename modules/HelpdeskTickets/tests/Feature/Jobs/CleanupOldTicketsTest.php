<?php

namespace Modules\HelpdeskTickets\Tests\Feature\Jobs;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Jobs\CleanupOldTickets;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Tests\TestCase;

/**
 * El job estaba programado a diario a las 02:00 y no tenía ningún test. Filtraba
 * por where('status', 'closed') y esa columna no existe — la tabla guarda el
 * estado como FK (status_id) —, así que lanzaba "Unknown column 'status'" en
 * cada ejecución, agotaba sus dos reintentos y moría. Nunca llegó a retirar un
 * solo ticket. El primer test de aquí abajo lo habría cazado el mismo día.
 */
class CleanupOldTicketsTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    private Customer $customer;

    private TicketStatus $closedStatus;

    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->helpdeskConnectionAvailable()) {
            $this->markTestSkipped('Helpdesk database connection is not available.');
        }

        config(['helpdesk.cleanup.closed_tickets_after_days' => 365]);

        $this->closedStatus = TicketStatus::firstOrCreate(
            ['slug' => 'closed'],
            ['name' => 'Closed', 'color' => '#6c757d', 'is_open' => false, 'order' => 9]
        );

        $this->customer = Customer::firstOrCreate(
            ['email' => 'cleanup-customer@example.com'],
            ['name' => 'Cleanup Customer']
        );
    }

    public function test_job_runs_without_error(): void
    {
        // El bug original era de esquema: la consulta ni siquiera llegaba a
        // ejecutarse. Con que el job complete su barrido ya se detecta.
        (new CleanupOldTickets)->handle();

        $this->assertTrue(true);
    }

    public function test_removes_tickets_closed_before_the_cutoff(): void
    {
        $old = $this->makeTicket(closedAt: now()->subDays(400));

        (new CleanupOldTickets)->handle();

        $this->assertSoftDeleted('helpdesk_tickets', ['id' => $old->id], 'helpdesk');
    }

    public function test_keeps_recently_closed_tickets(): void
    {
        $recent = $this->makeTicket(closedAt: now()->subDays(30));

        (new CleanupOldTickets)->handle();

        $this->assertNotSoftDeleted('helpdesk_tickets', ['id' => $recent->id], 'helpdesk');
    }

    public function test_keeps_tickets_that_are_still_open(): void
    {
        // Sin closed_at no hay nada que retirar por antigüedad, por vieja que
        // sea la fila.
        $open = $this->makeTicket(closedAt: null);
        $open->forceFill(['created_at' => now()->subDays(900)])->saveQuietly();

        (new CleanupOldTickets)->handle();

        $this->assertNotSoftDeleted('helpdesk_tickets', ['id' => $open->id], 'helpdesk');
    }

    private function makeTicket(?Carbon $closedAt): Ticket
    {
        $ticket = Ticket::create([
            'subject' => 'Cleanup test ticket',
            'description' => 'Cleanup test description',
            'customer_id' => $this->customer->id,
            'status_id' => $this->closedStatus->id,
            'priority' => 'normal',
            'source' => 'portal',
        ]);

        if ($closedAt) {
            $ticket->forceFill(['closed_at' => $closedAt])->saveQuietly();
        }

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
