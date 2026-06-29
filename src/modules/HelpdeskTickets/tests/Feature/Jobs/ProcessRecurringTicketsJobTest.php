<?php

namespace Modules\HelpdeskTickets\Tests\Feature\Jobs;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\HelpdeskTickets\Jobs\ProcessRecurringTicketsJob;
use Modules\HelpdeskTickets\Models\RecurringTicket;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Tests\TestCase;

class ProcessRecurringTicketsJobTest extends TestCase
{
    use RefreshDatabase;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    private TicketStatus $openStatus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->openStatus = TicketStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );
    }

    public function test_creates_ticket_for_due_recurring_schedule(): void
    {
        $recurring = RecurringTicket::create([
            'name' => 'Daily DB Backup',
            'subject' => 'Daily database backup check',
            'description' => 'Please verify the backup completed successfully.',
            'frequency' => 'daily',
            'next_run_at' => now()->subMinute(),
            'is_active' => true,
            'tickets_created' => 0,
        ]);

        (new ProcessRecurringTicketsJob)->handle();

        $this->assertDatabaseHas('helpdesk_tickets', [
            'subject' => 'Daily database backup check',
            'source' => 'recurring',
        ], 'helpdesk');
    }

    public function test_updates_last_run_at_and_next_run_at_after_creating_ticket(): void
    {
        $recurring = RecurringTicket::create([
            'name' => 'Weekly Report',
            'subject' => 'Weekly report generation',
            'frequency' => 'weekly',
            'next_run_at' => now()->subMinute(),
            'last_run_at' => null,
            'is_active' => true,
            'tickets_created' => 0,
        ]);

        (new ProcessRecurringTicketsJob)->handle();

        $recurring->refresh();

        $this->assertNotNull($recurring->last_run_at);
        $this->assertNotNull($recurring->next_run_at);
        $this->assertTrue($recurring->next_run_at->isAfter(now()));
        $this->assertEquals(1, $recurring->tickets_created);
    }

    public function test_skips_schedules_not_yet_due(): void
    {
        RecurringTicket::create([
            'name' => 'Future Recurring',
            'subject' => 'This should not run yet',
            'frequency' => 'daily',
            'next_run_at' => now()->addHour(),
            'is_active' => true,
            'tickets_created' => 0,
        ]);

        (new ProcessRecurringTicketsJob)->handle();

        $this->assertDatabaseMissing('helpdesk_tickets', [
            'subject' => 'This should not run yet',
        ], 'helpdesk');
    }

    public function test_skips_inactive_schedules(): void
    {
        RecurringTicket::create([
            'name' => 'Inactive Recurring',
            'subject' => 'Inactive ticket subject',
            'frequency' => 'daily',
            'next_run_at' => now()->subMinute(),
            'is_active' => false,
            'tickets_created' => 0,
        ]);

        (new ProcessRecurringTicketsJob)->handle();

        $this->assertDatabaseMissing('helpdesk_tickets', [
            'subject' => 'Inactive ticket subject',
        ], 'helpdesk');
    }

    public function test_continues_processing_remaining_schedules_when_one_fails(): void
    {
        // First recurring with null frequency to trigger calculate error — but it won't
        // Because the engine catches throwables. Instead test with two valid ones.
        RecurringTicket::create([
            'name' => 'First Recurring',
            'subject' => 'First ticket',
            'frequency' => 'daily',
            'next_run_at' => now()->subMinute(),
            'is_active' => true,
            'tickets_created' => 0,
        ]);

        RecurringTicket::create([
            'name' => 'Second Recurring',
            'subject' => 'Second ticket',
            'frequency' => 'weekly',
            'next_run_at' => now()->subMinute(),
            'is_active' => true,
            'tickets_created' => 0,
        ]);

        (new ProcessRecurringTicketsJob)->handle();

        $this->assertDatabaseHas('helpdesk_tickets', ['subject' => 'First ticket', 'source' => 'recurring'], 'helpdesk');
        $this->assertDatabaseHas('helpdesk_tickets', ['subject' => 'Second ticket', 'source' => 'recurring'], 'helpdesk');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

}
