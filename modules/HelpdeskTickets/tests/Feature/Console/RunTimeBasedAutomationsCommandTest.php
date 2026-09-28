<?php

namespace Modules\HelpdeskTickets\Tests\Feature\Console;

use Modules\HelpdeskTickets\Models\Automation;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Tests\Concerns\SharesHelpdeskPdo;
use Tests\TestCase;

/**
 * ticket:run-time-automations — reglas "Periódicamente" (trigger_event
 * ticket.time_elapsed) sobre tickets inactivos desde hace N horas.
 */
class RunTimeBasedAutomationsCommandTest extends TestCase
{
    use SharesHelpdeskPdo;

    private function staleAutomation(): Automation
    {
        return Automation::factory()->create([
            'trigger_event' => 'ticket.time_elapsed',
            'is_active' => true,
            'conditions' => [
                ['field' => 'hours_since_last_activity', 'op' => 'greater_than', 'value' => 4],
            ],
            'match_mode' => 'all',
            'actions' => [
                ['type' => 'set_priority', 'value' => 'urgent'],
            ],
        ]);
    }

    public function test_runs_matching_automation_on_a_stale_ticket(): void
    {
        $this->staleAutomation();
        $ticket = Ticket::factory()->create([
            'priority' => 'normal',
            'last_activity_at' => now()->subHours(6),
        ]);

        $this->artisan('ticket:run-time-automations')->assertSuccessful();

        $this->assertSame('urgent', $ticket->fresh()->priority);
    }

    public function test_does_not_run_when_ticket_has_not_been_inactive_long_enough(): void
    {
        $this->staleAutomation();
        $ticket = Ticket::factory()->create([
            'priority' => 'normal',
            'last_activity_at' => now()->subHours(1),
        ]);

        $this->artisan('ticket:run-time-automations')->assertSuccessful();

        $this->assertSame('normal', $ticket->fresh()->priority);
    }

    public function test_ignores_inactive_automations(): void
    {
        Automation::factory()->create([
            'trigger_event' => 'ticket.time_elapsed',
            'is_active' => false,
            'conditions' => [
                ['field' => 'hours_since_last_activity', 'op' => 'greater_than', 'value' => 4],
            ],
            'actions' => [
                ['type' => 'set_priority', 'value' => 'urgent'],
            ],
        ]);
        $ticket = Ticket::factory()->create([
            'priority' => 'normal',
            'last_activity_at' => now()->subHours(6),
        ]);

        $this->artisan('ticket:run-time-automations')->assertSuccessful();

        $this->assertSame('normal', $ticket->fresh()->priority);
    }

    public function test_skips_snoozed_tickets(): void
    {
        $this->staleAutomation();
        $ticket = Ticket::factory()->create([
            'priority' => 'normal',
            'last_activity_at' => now()->subHours(6),
            'snoozed_until' => now()->addDay(),
        ]);

        $this->artisan('ticket:run-time-automations')->assertSuccessful();

        $this->assertSame('normal', $ticket->fresh()->priority);
    }

    public function test_skips_closed_tickets(): void
    {
        $this->staleAutomation();
        $ticket = Ticket::factory()->create([
            'priority' => 'normal',
            'last_activity_at' => now()->subHours(6),
            'closed_at' => now()->subHour(),
        ]);

        $this->artisan('ticket:run-time-automations')->assertSuccessful();

        $this->assertSame('normal', $ticket->fresh()->priority);
    }

    public function test_succeeds_without_running_anything_when_no_time_based_automations_exist(): void
    {
        Ticket::factory()->create([
            'priority' => 'normal',
            'last_activity_at' => now()->subHours(6),
        ]);

        $this->artisan('ticket:run-time-automations')->assertSuccessful();
    }
}
