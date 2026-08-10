<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

use Illuminate\Mail\SendQueuedMailable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketMail;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Tests\Concerns\SharesHelpdeskPdo;
use Tests\TestCase;

class SendScheduledTicketMailsCommandTest extends TestCase
{
    use SharesHelpdeskPdo;

    private TicketStatus $status;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->helpdeskConnectionAvailable()) {
            $this->markTestSkipped('Helpdesk database connection is not available.');
        }

        $this->status = TicketStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );
        $this->customer = Customer::factory()->create();
    }

    public function test_command_delivers_due_scheduled_mails(): void
    {
        Queue::fake();

        $ticket = $this->createTicket();
        $due = $this->createScheduledMail($ticket, ['scheduled_at' => now()->subMinute()]);
        $future = $this->createScheduledMail($ticket, ['scheduled_at' => now()->addHour()]);

        $this->artisan('helpdesk:send-scheduled-emails')->assertSuccessful();

        $this->assertSame('sent', $due->fresh()->status);
        $this->assertSame('scheduled', $future->fresh()->status);
        Queue::assertPushed(SendQueuedMailable::class, 1);
    }

    public function test_command_marks_as_failed_when_ticket_no_longer_exists(): void
    {
        Queue::fake();

        $ticket = $this->createTicket();
        $mail = $this->createScheduledMail($ticket, ['scheduled_at' => now()->subMinute()]);

        // Ticket::ticket() es un belongsTo normal: el scope global de
        // SoftDeletes lo excluye igual que si ya no existiera, sin violar el
        // FK de helpdesk_ticket_mails.ticket_id (onDelete cascade es solo
        // para borrado físico).
        $ticket->delete();

        $this->artisan('helpdesk:send-scheduled-emails')->assertSuccessful();

        $fresh = $mail->fresh();
        $this->assertSame('failed', $fresh->status);
        Queue::assertNothingPushed();
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    private function createTicket(): Ticket
    {
        return Ticket::create([
            'subject' => 'Scheduled mail command test ticket',
            'description' => 'Test description.',
            'customer_id' => $this->customer->id,
            'status_id' => $this->status->id,
            'priority' => 'normal',
            'source' => 'web',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createScheduledMail(Ticket $ticket, array $overrides = []): TicketMail
    {
        return TicketMail::create(array_merge([
            'ticket_id' => $ticket->id,
            'direction' => 'outbound',
            'from' => 'soporte@alvarez.mx',
            'to' => 'cliente@example.com',
            'subject' => 'Seguimiento programado',
            'body_html' => '<p>Seguimiento</p>',
            'body_text' => 'Seguimiento',
            'status' => 'scheduled',
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
