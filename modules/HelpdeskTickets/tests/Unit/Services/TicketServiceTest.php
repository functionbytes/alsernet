<?php

namespace Modules\HelpdeskTickets\Tests\Unit\Services;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Setting as HelpdeskGeneralSetting;
use Modules\HelpdeskTickets\Events\TicketClosed;
use Modules\HelpdeskTickets\Events\TicketCreated;
use Modules\HelpdeskTickets\Events\TicketReopened;
use Modules\HelpdeskTickets\Events\TicketUpdated;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketHistory;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Services\TicketService;
use Tests\TestCase;

class TicketServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    private function helpdeskConnectionAvailable(): bool
    {
        try {
            DB::connection('helpdesk')->getPdo();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function makeService(): TicketService
    {
        return new TicketService;
    }

    private function ticketData(array $overrides = []): array
    {
        return array_merge(['customer_id' => Customer::factory()->create()->id], $overrides);
    }

    private function ensureStatusExists(string $slug): TicketStatus
    {
        return TicketStatus::firstOrCreate(
            ['slug' => $slug],
            [
                'name' => ucfirst($slug),
                'color' => '#000000',
                'order' => 1,
                'is_default' => false,
                'is_system' => true,
                'is_open' => $slug === 'new',
                'stops_sla_timer' => $slug === 'closed',
                'active' => true,
            ]
        );
    }

    // ─── createTicket ─────────────────────────────────────────────────────────

    public function test_create_ticket_generates_ticket_number_and_sets_default_status(): void
    {
        if (! $this->helpdeskConnectionAvailable()) {
            $this->markTestSkipped('Helpdesk database connection is not available.');
        }

        Event::fake();

        $status = $this->ensureStatusExists('new');

        $service = $this->makeService();

        $ticket = $service->createTicket($this->ticketData([
            'subject' => 'Test ticket subject',
            'description' => 'Test description',
            'priority' => 'normal',
            'source' => 'email',
        ]));

        // El prefijo es TCK-, no TKT-. TicketService tenía su propio generador
        // de números con un prefijo distinto al del resto del módulo, y este
        // test fijaba ese comportamiento: los tickets del widget y del
        // formulario público salían con TKT- y el hilado del correo entrante,
        // que busca /#(TCK-\d{4}-\d{5})/, nunca los reconocía. Ahora delega en
        // Ticket::generateTicketNumber(), que es la única implementación.
        $this->assertStringStartsWith('TCK-'.now()->year.'-', $ticket->ticket_number);
        $this->assertEquals($status->id, $ticket->status_id);
    }

    public function test_create_ticket_dispatches_ticket_created_event(): void
    {
        if (! $this->helpdeskConnectionAvailable()) {
            $this->markTestSkipped('Helpdesk database connection is not available.');
        }

        Event::fake();

        $this->ensureStatusExists('new');

        $service = $this->makeService();
        $service->createTicket($this->ticketData([
            'subject' => 'Event dispatch test',
            'priority' => 'normal',
            'source' => 'web',
        ]));

        Event::assertDispatched(TicketCreated::class);
    }

    public function test_create_ticket_increments_ticket_number_sequentially(): void
    {
        if (! $this->helpdeskConnectionAvailable()) {
            $this->markTestSkipped('Helpdesk database connection is not available.');
        }

        Event::fake();

        $this->ensureStatusExists('new');
        $service = $this->makeService();

        $first = $service->createTicket($this->ticketData(['subject' => 'First', 'priority' => 'normal', 'source' => 'web']));
        $second = $service->createTicket($this->ticketData(['subject' => 'Second', 'priority' => 'normal', 'source' => 'web']));

        $firstNumber = (int) substr($first->ticket_number, -5);
        $secondNumber = (int) substr($second->ticket_number, -5);

        $this->assertEquals(1, $secondNumber - $firstNumber);
    }

    // ─── updateTicket ─────────────────────────────────────────────────────────

    public function test_update_ticket_dispatches_ticket_updated_event(): void
    {
        if (! $this->helpdeskConnectionAvailable()) {
            $this->markTestSkipped('Helpdesk database connection is not available.');
        }

        Event::fake();

        $this->ensureStatusExists('new');
        $service = $this->makeService();

        $ticket = $service->createTicket($this->ticketData([
            'subject' => 'Original subject',
            'priority' => 'normal',
            'source' => 'web',
        ]));

        Event::fake([TicketUpdated::class]);

        $service->updateTicket($ticket, ['subject' => 'Updated subject']);

        Event::assertDispatched(TicketUpdated::class, function (TicketUpdated $event) use ($ticket) {
            return $event->ticket->id === $ticket->id;
        });
    }

    public function test_update_ticket_logs_status_change_history(): void
    {
        if (! $this->helpdeskConnectionAvailable()) {
            $this->markTestSkipped('Helpdesk database connection is not available.');
        }

        Event::fake();

        $originalStatus = $this->ensureStatusExists('new');
        $newStatus = $this->ensureStatusExists('in-progress');

        $service = $this->makeService();
        $ticket = $service->createTicket($this->ticketData([
            'subject' => 'History test',
            'status_id' => $originalStatus->id,
            'priority' => 'normal',
            'source' => 'web',
        ]));

        $service->updateTicket($ticket, ['status_id' => $newStatus->id]);

        $history = TicketHistory::where('ticket_id', $ticket->id)
            ->where('field_name', 'status_id')
            ->first();

        $this->assertNotNull($history);
        $this->assertEquals('updated', $history->action_type);
    }

    // ─── closeTicket ──────────────────────────────────────────────────────────

    public function test_close_ticket_updates_status_and_dispatches_closed_event(): void
    {
        if (! $this->helpdeskConnectionAvailable()) {
            $this->markTestSkipped('Helpdesk database connection is not available.');
        }

        Event::fake();

        $this->ensureStatusExists('new');
        $closedStatus = $this->ensureStatusExists('closed');

        $service = $this->makeService();
        $ticket = $service->createTicket($this->ticketData([
            'subject' => 'To be closed',
            'priority' => 'normal',
            'source' => 'web',
        ]));

        Event::fake([TicketClosed::class]);
        $closed = $service->closeTicket($ticket);

        $this->assertNotNull($closed->closed_at);
        $this->assertEquals($closedStatus->id, $closed->status_id);
        Event::assertDispatched(TicketClosed::class);
    }

    public function test_close_ticket_throws_when_already_closed(): void
    {
        if (! $this->helpdeskConnectionAvailable()) {
            $this->markTestSkipped('Helpdesk database connection is not available.');
        }

        Event::fake();

        $this->ensureStatusExists('new');
        $this->ensureStatusExists('closed');

        $service = $this->makeService();
        $ticket = $service->createTicket($this->ticketData([
            'subject' => 'Already closed test',
            'priority' => 'normal',
            'source' => 'web',
        ]));

        $service->closeTicket($ticket);
        $ticket->refresh();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Ticket is already closed');

        $service->closeTicket($ticket);
    }

    // ─── reopenTicket ─────────────────────────────────────────────────────────

    public function test_reopen_ticket_restores_active_status(): void
    {
        if (! $this->helpdeskConnectionAvailable()) {
            $this->markTestSkipped('Helpdesk database connection is not available.');
        }

        Event::fake();

        $this->ensureStatusExists('new');
        $this->ensureStatusExists('closed');

        $service = $this->makeService();
        $ticket = $service->createTicket($this->ticketData([
            'subject' => 'To be reopened',
            'priority' => 'normal',
            'source' => 'web',
        ]));

        $service->closeTicket($ticket);
        $ticket->refresh();

        Event::fake([TicketReopened::class]);
        $reopened = $service->reopenTicket($ticket);

        $this->assertNull($reopened->closed_at);
        Event::assertDispatched(TicketReopened::class);
    }

    public function test_reopen_ticket_throws_when_not_closed(): void
    {
        if (! $this->helpdeskConnectionAvailable()) {
            $this->markTestSkipped('Helpdesk database connection is not available.');
        }

        Event::fake();

        $this->ensureStatusExists('new');

        $service = $this->makeService();
        $ticket = $service->createTicket($this->ticketData([
            'subject' => 'Not closed test',
            'priority' => 'normal',
            'source' => 'web',
        ]));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Ticket is not closed');

        $service->reopenTicket($ticket);
    }

    // ─── reopenIfCustomerCanReopen ──────────────────────────────────────────────
    //
    // tickets.user_reopen_issue/user_reopen_time existían en Settings → General
    // desde antes de esta sesión, pero sin ningún efecto real (bug real
    // encontrado 4-sep-2026, TCK-2026-00093): un cliente respondiendo a un
    // ticket cerrado (por email o portal) nunca lo reabría.

    private function makeClosedTicket(): Ticket
    {
        $this->ensureStatusExists('new');
        $this->ensureStatusExists('closed');

        $service = $this->makeService();
        $ticket = $service->createTicket($this->ticketData([
            'subject' => 'Reopen eligibility test',
            'priority' => 'normal',
            'source' => 'web',
        ]));

        $service->closeTicket($ticket);

        return $ticket->refresh();
    }

    public function test_reopen_if_eligible_no_hace_nada_si_el_ticket_no_esta_cerrado(): void
    {
        if (! $this->helpdeskConnectionAvailable()) {
            $this->markTestSkipped('Helpdesk database connection is not available.');
        }

        Event::fake();
        $this->ensureStatusExists('new');

        $service = $this->makeService();
        $ticket = $service->createTicket($this->ticketData(['subject' => 'Open ticket', 'priority' => 'normal', 'source' => 'web']));

        Event::fake([TicketReopened::class]);
        $service->reopenIfCustomerCanReopen($ticket);

        Event::assertNotDispatched(TicketReopened::class);
    }

    public function test_reopen_if_eligible_reabre_dentro_de_la_ventana(): void
    {
        if (! $this->helpdeskConnectionAvailable()) {
            $this->markTestSkipped('Helpdesk database connection is not available.');
        }

        Event::fake();
        HelpdeskGeneralSetting::set('tickets.user_reopen_issue', true, 'tickets');
        HelpdeskGeneralSetting::set('tickets.user_reopen_time', 7, 'tickets');

        $ticket = $this->makeClosedTicket();

        Event::fake([TicketReopened::class]);
        $this->makeService()->reopenIfCustomerCanReopen($ticket);

        $this->assertNull($ticket->fresh()->closed_at);
        Event::assertDispatched(TicketReopened::class);
    }

    public function test_reopen_if_eligible_respeta_el_interruptor_apagado(): void
    {
        if (! $this->helpdeskConnectionAvailable()) {
            $this->markTestSkipped('Helpdesk database connection is not available.');
        }

        Event::fake();
        HelpdeskGeneralSetting::set('tickets.user_reopen_issue', false, 'tickets');

        $ticket = $this->makeClosedTicket();

        Event::fake([TicketReopened::class]);
        $this->makeService()->reopenIfCustomerCanReopen($ticket);

        $this->assertNotNull($ticket->fresh()->closed_at);
        Event::assertNotDispatched(TicketReopened::class);
    }

    public function test_reopen_if_eligible_respeta_la_ventana_de_dias(): void
    {
        if (! $this->helpdeskConnectionAvailable()) {
            $this->markTestSkipped('Helpdesk database connection is not available.');
        }

        Event::fake();
        HelpdeskGeneralSetting::set('tickets.user_reopen_issue', true, 'tickets');
        HelpdeskGeneralSetting::set('tickets.user_reopen_time', 7, 'tickets');

        $ticket = $this->makeClosedTicket();
        // Cerrado hace 10 días: fuera de la ventana de 7.
        $ticket->forceFill(['closed_at' => now()->subDays(10)])->save();

        Event::fake([TicketReopened::class]);
        $this->makeService()->reopenIfCustomerCanReopen($ticket);

        $this->assertNotNull($ticket->fresh()->closed_at);
        Event::assertNotDispatched(TicketReopened::class);
    }
}
