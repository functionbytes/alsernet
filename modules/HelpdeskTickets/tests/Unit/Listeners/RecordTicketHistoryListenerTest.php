<?php

namespace Modules\HelpdeskTickets\Tests\Unit\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Modules\HelpdeskTickets\Events\TicketCreated;
use Modules\HelpdeskTickets\Listeners\RecordTicketHistory;
use Modules\HelpdeskTickets\Models\Ticket;
use Tests\TestCase;

class RecordTicketHistoryListenerTest extends TestCase
{
    private function helpdeskConnectionAvailable(): bool
    {
        try {
            DB::connection('helpdesk')->getPdo();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    // ─── structural contracts ─────────────────────────────────────────────────

    public function test_listener_implements_should_queue(): void
    {
        $listener = new RecordTicketHistory;

        $this->assertInstanceOf(ShouldQueue::class, $listener);
    }

    public function test_listener_is_on_helpdesk_audit_queue(): void
    {
        $listener = new RecordTicketHistory;

        $this->assertEquals('helpdesk-audit', $listener->queue);
    }

    public function test_listener_retries_three_times(): void
    {
        $listener = new RecordTicketHistory;

        $this->assertEquals(3, $listener->tries);
    }

    public function test_listener_has_backoff_strategy(): void
    {
        $listener = new RecordTicketHistory;

        $this->assertIsArray($listener->backoff);
        $this->assertNotEmpty($listener->backoff);
    }

    // ─── handle ───────────────────────────────────────────────────────────────

    public function test_listener_handles_ticket_created_event_and_logs_history(): void
    {
        if (! $this->helpdeskConnectionAvailable()) {
            $this->markTestSkipped('Helpdesk database connection is not available.');
        }

        // Insert a minimal ticket directly
        $ticketId = DB::connection('helpdesk')->table('helpdesk_tickets')->insertGetId([
            'ticket_number' => 'TKT-HIST-'.uniqid(),
            'subject' => 'History listener test',
            'priority' => 'normal',
            'source' => 'web',
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ]);

        $ticket = Ticket::on('helpdesk')->find($ticketId);

        $event = new class($ticket)
        {
            public Ticket $ticket;

            public function __construct(Ticket $ticket)
            {
                $this->ticket = $ticket;
            }
        };

        // Cast to TicketCreated-compatible anonymous class
        $ticketCreatedEvent = new class($ticket) extends TicketCreated
        {
            public function __construct(Ticket $ticket)
            {
                $this->ticket = $ticket;
            }
        };

        $listener = new RecordTicketHistory;
        $listener->handle($ticketCreatedEvent);

        $history = DB::connection('helpdesk')
            ->table('helpdesk_ticket_histories')
            ->where('ticket_id', $ticketId)
            ->where('action', 'ticket_created')
            ->first();

        $this->assertNotNull($history);
        $this->assertEquals('ticket_created', $history->action);
        $this->assertEquals('Ticket creado', $history->description);

        // Cleanup
        DB::connection('helpdesk')->table('helpdesk_ticket_histories')->where('ticket_id', $ticketId)->delete();
        DB::connection('helpdesk')->table('helpdesk_tickets')->where('id', $ticketId)->delete();
    }

    public function test_listener_ignores_unknown_event_types(): void
    {
        $listener = new RecordTicketHistory;

        // Should not throw for an unknown event
        $unknownEvent = new \stdClass;

        try {
            $listener->handle($unknownEvent);
            $this->assertTrue(true);
        } catch (\Throwable $e) {
            $this->fail('Listener should not throw for unknown event: '.$e->getMessage());
        }
    }
}
