<?php

namespace Modules\HelpdeskTickets\Tests\Feature\Console;

use Illuminate\Support\Facades\Event;
use Modules\Helpdesk\Models\Setting;
use Modules\HelpdeskTickets\Events\SlaBreached;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketSlaBreach;
use Modules\HelpdeskTickets\Models\TicketSlaPolicy;
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
        Ticket::query()->whereKey($ticket->id)->update([
            'sla_resolution_due_at' => null,
            'sla_policy_id' => TicketSlaPolicy::factory()->create()->id,
        ]);
        Ticket::withoutTimestamps(fn () => $ticket->forceFill(['created_at' => now()->subDays(10)])->save());

        $this->artisan('ticket:autooverdue')->assertSuccessful();

        $this->assertTrue($ticket->fresh()->sla_resolution_breached);
    }

    public function test_fallback_days_do_not_flag_tickets_without_sla_policy(): void
    {
        $ticket = Ticket::factory()->create(['sla_resolution_breached' => false]);

        Ticket::query()->whereKey($ticket->id)->update([
            'sla_resolution_due_at' => null,
            'sla_policy_id' => null,
        ]);
        Ticket::withoutTimestamps(fn () => $ticket->forceFill(['created_at' => now()->subDays(10)])->save());

        $this->artisan('ticket:autooverdue')->assertSuccessful();

        $this->assertFalse($ticket->fresh()->sla_resolution_breached);
    }

    public function test_resolution_breach_goes_through_sla_breach_path_once(): void
    {
        Event::fake([SlaBreached::class]);

        $ticket = Ticket::factory()->create([
            'sla_resolution_due_at' => now()->subHour(),
            'sla_resolution_breached' => false,
        ]);

        $this->artisan('ticket:autooverdue')->assertSuccessful();
        $this->artisan('ticket:autooverdue')->assertSuccessful();

        $this->assertCount(
            1,
            Event::dispatched(SlaBreached::class, fn (SlaBreached $e) => $e->ticket->id === $ticket->id)
        );
        $this->assertSame(
            1,
            TicketSlaBreach::query()->where('ticket_id', $ticket->id)->count()
        );
    }

    public function test_marks_both_due_date_and_fallback_branches_and_skips_the_rest_in_one_run(): void
    {
        $duePassed = Ticket::factory()->create(['sla_resolution_due_at' => now()->subHour(), 'sla_resolution_breached' => false]);
        $dueFuture = Ticket::factory()->create(['sla_resolution_due_at' => now()->addHour(), 'sla_resolution_breached' => false]);
        $fallback = Ticket::factory()->create(['sla_resolution_breached' => false]);
        $recentNoDue = Ticket::factory()->create(['sla_resolution_breached' => false]);

        // Ver test_marks_resolution_breach_via_fallback_days_when_no_due_date_set:
        // update crudo para esquivar la política que el observer asigna al crear.
        Ticket::query()->whereKey([$fallback->id, $recentNoDue->id])->update(['sla_resolution_due_at' => null]);
        Ticket::withoutTimestamps(fn () => $fallback->forceFill(['created_at' => now()->subDays(10)])->save());

        $this->artisan('ticket:autooverdue')->assertSuccessful();

        $this->assertTrue($duePassed->fresh()->sla_resolution_breached);
        $this->assertFalse($dueFuture->fresh()->sla_resolution_breached);
        $this->assertTrue($fallback->fresh()->sla_resolution_breached);
        $this->assertFalse($recentNoDue->fresh()->sla_resolution_breached);
    }

    public function test_null_next_response_due_is_not_a_breach(): void
    {
        $ticket = Ticket::factory()->create([
            'sla_next_response_due_at' => null,
            'sla_next_response_breached' => false,
            'last_message_at' => now()->subDays(30),
            'created_at' => now()->subDays(30),
        ]);

        $this->artisan('ticket:autooverdue')->assertSuccessful();

        $this->assertFalse($ticket->fresh()->sla_next_response_breached);
    }
}
