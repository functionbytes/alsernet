<?php

namespace Modules\HelpdeskTickets\Tests\Feature\Console;

use Modules\Helpdesk\Models\Setting;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Tests\Concerns\SharesHelpdeskPdo;
use Tests\TestCase;

class MarkOverdueTicketsCommandTest extends TestCase
{
    use SharesHelpdeskPdo;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::set('tickets.auto_overdue_ticket', true);
        Setting::set('tickets.auto_overdue_ticket_time', 5);
    }

    public function test_disabled_setting_marks_nothing_and_succeeds(): void
    {
        Setting::set('tickets.auto_overdue_ticket', false);

        $ticket = Ticket::factory()->create([
            'sla_resolution_due_at' => now()->subDay(),
            'sla_resolution_breached' => false,
        ]);

        $this->artisan('ticket:autooverdue')->assertSuccessful();

        $this->assertFalse($ticket->fresh()->sla_resolution_breached);
    }

    public function test_marks_resolution_breach_when_due_date_passed(): void
    {
        $ticket = Ticket::factory()->create([
            'sla_resolution_due_at' => now()->subHour(),
            'sla_resolution_breached' => false,
        ]);

        $this->artisan('ticket:autooverdue')->assertSuccessful();

        $this->assertTrue($ticket->fresh()->sla_resolution_breached);
    }

    public function test_does_not_mark_resolution_breach_with_future_due_date(): void
    {
        $ticket = Ticket::factory()->create([
            'sla_resolution_due_at' => now()->addHour(),
            'sla_resolution_breached' => false,
        ]);

        $this->artisan('ticket:autooverdue')->assertSuccessful();

        $this->assertFalse($ticket->fresh()->sla_resolution_breached);
    }

    public function test_does_not_mark_resolution_breach_for_closed_ticket(): void
    {
        $ticket = Ticket::factory()->create([
            'closed_at' => now()->subMinute(),
            'sla_resolution_due_at' => now()->subHour(),
            'sla_resolution_breached' => false,
        ]);

        $this->artisan('ticket:autooverdue')->assertSuccessful();

        $this->assertFalse($ticket->fresh()->sla_resolution_breached);
    }

    public function test_does_not_mark_resolution_breach_while_sla_is_paused(): void
    {
        $ticket = Ticket::factory()->create([
            'sla_resolution_due_at' => now()->subHour(),
            'sla_resolution_breached' => false,
            'sla_paused_at' => now()->subMinutes(30),
        ]);

        $this->artisan('ticket:autooverdue')->assertSuccessful();

        $this->assertFalse($ticket->fresh()->sla_resolution_breached);
    }

    public function test_marks_resolution_breach_via_fallback_days_when_no_due_date_set(): void
    {
        $ticket = Ticket::factory()->create(['sla_resolution_breached' => false]);

        // TicketObserver::creating() auto-resolves a real SLA policy for any
        // ticket that doesn't explicitly set one, filling due dates on
        // insert. A raw query-builder update (no model events) is required
        // here to get back to the "no SLA policy applies" case this test
        // means to cover, without fighting that auto-assignment.
        Ticket::query()->whereKey($ticket->id)->update(['sla_resolution_due_at' => null]);
        Ticket::withoutTimestamps(fn () => $ticket->forceFill(['created_at' => now()->subDays(10)])->save());

        $this->artisan('ticket:autooverdue')->assertSuccessful();

        $this->assertTrue($ticket->fresh()->sla_resolution_breached);
    }

    public function test_marks_first_response_breach_when_due_date_passed_and_unanswered(): void
    {
        $ticket = Ticket::factory()->create([
            'first_response_at' => null,
            'sla_first_response_due_at' => now()->subHour(),
            'sla_first_response_breached' => false,
        ]);

        $this->artisan('ticket:autooverdue')->assertSuccessful();

        $this->assertTrue($ticket->fresh()->sla_first_response_breached);
    }

    public function test_does_not_mark_first_response_breach_when_already_answered(): void
    {
        $ticket = Ticket::factory()->create([
            'first_response_at' => now()->subMinutes(10),
            'sla_first_response_due_at' => now()->subHour(),
            'sla_first_response_breached' => false,
        ]);

        $this->artisan('ticket:autooverdue')->assertSuccessful();

        $this->assertFalse($ticket->fresh()->sla_first_response_breached);
    }

    public function test_does_not_mark_first_response_breach_for_closed_ticket(): void
    {
        $ticket = Ticket::factory()->create([
            'closed_at' => now()->subMinute(),
            'first_response_at' => null,
            'sla_first_response_due_at' => now()->subHour(),
            'sla_first_response_breached' => false,
        ]);

        $this->artisan('ticket:autooverdue')->assertSuccessful();

        $this->assertFalse($ticket->fresh()->sla_first_response_breached);
    }

    public function test_marks_next_response_breach_when_due_date_passed(): void
    {
        $ticket = Ticket::factory()->create([
            'sla_next_response_due_at' => now()->subHour(),
            'sla_next_response_breached' => false,
        ]);

        $this->artisan('ticket:autooverdue')->assertSuccessful();

        $this->assertTrue($ticket->fresh()->sla_next_response_breached);
    }

    public function test_does_not_mark_next_response_breach_for_closed_ticket(): void
    {
        $ticket = Ticket::factory()->create([
            'closed_at' => now()->subMinute(),
            'sla_next_response_due_at' => now()->subHour(),
            'sla_next_response_breached' => false,
        ]);

        $this->artisan('ticket:autooverdue')->assertSuccessful();

        $this->assertFalse($ticket->fresh()->sla_next_response_breached);
    }
}
