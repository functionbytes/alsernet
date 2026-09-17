<?php

namespace Modules\HelpdeskTickets\Tests\Unit\Services;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Modules\HelpdeskTickets\Events\TicketAssigned;
use Modules\HelpdeskTickets\Events\TicketUnassigned;
use Modules\HelpdeskTickets\Exceptions\StaleTicketException;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Services\TicketUpdateService;
use Tests\TestCase;

/**
 * QUAL-09 / item 1: TicketUpdateService::applyChanges() is the only path for
 * inline ticket edits (from the ticket detail form) and had no test coverage.
 *
 * AssignmentService::assignTicket() already fires TicketAssigned when a ticket
 * is assigned via the assignment flow; this class covers the inline-edit path,
 * which is a separate code path that had the exact same bug fixed here (see
 * TicketUpdateService.php:86-94).
 */
class TicketUpdateServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = [null, 'helpdesk'];

    private TicketUpdateService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new TicketUpdateService;
    }

    // ─── status_id / SLA pause-resume ──────────────────────────────────────────

    public function test_moving_to_a_status_that_stops_the_sla_timer_pauses_it(): void
    {
        $openStatus = TicketStatus::factory()->open()->create();
        $pausingStatus = TicketStatus::factory()->create(['stops_sla_timer' => true]);
        $ticket = Ticket::factory()->create(['status_id' => $openStatus->id]);
        $actor = User::factory()->create();

        $changed = $this->service->applyChanges($ticket, ['status_id' => $pausingStatus->id], $actor);

        $fresh = $ticket->fresh();
        $this->assertContains('status_id', $changed);
        $this->assertSame($pausingStatus->id, $fresh->status_id);
        $this->assertNotNull($fresh->sla_paused_at);
    }

    public function test_moving_back_to_a_normal_status_resumes_the_sla_timer(): void
    {
        $pausingStatus = TicketStatus::factory()->create(['stops_sla_timer' => true]);
        $openStatus = TicketStatus::factory()->open()->create();
        $ticket = Ticket::factory()->create([
            'status_id' => $pausingStatus->id,
            'sla_paused_at' => now()->subMinutes(30),
            'sla_paused_duration_minutes' => 0,
        ]);
        $actor = User::factory()->create();

        $this->service->applyChanges($ticket, ['status_id' => $openStatus->id], $actor);

        $fresh = $ticket->fresh();
        $this->assertNull($fresh->sla_paused_at);
        $this->assertGreaterThanOrEqual(30, $fresh->sla_paused_duration_minutes);
    }

    public function test_switching_between_two_pausing_statuses_does_not_re_pause(): void
    {
        $pausingStatusA = TicketStatus::factory()->create(['stops_sla_timer' => true]);
        $pausingStatusB = TicketStatus::factory()->create(['stops_sla_timer' => true]);
        $pausedAt = now()->subHour();
        $ticket = Ticket::factory()->create([
            'status_id' => $pausingStatusA->id,
            'sla_paused_at' => $pausedAt,
        ]);
        $actor = User::factory()->create();

        $this->service->applyChanges($ticket, ['status_id' => $pausingStatusB->id], $actor);

        $fresh = $ticket->fresh();
        $this->assertSame($pausingStatusB->id, $fresh->status_id);
        // The clock was already paused by statusA: moving to another pausing
        // status must not touch sla_paused_at (no "re-pause" that would reset
        // how long it has actually been paused).
        $this->assertEqualsWithDelta($pausedAt->timestamp, $fresh->sla_paused_at->timestamp, 1);
    }

    // ─── assignee_id / TicketAssigned / TicketUnassigned ───────────────────────

    public function test_assigning_a_ticket_dispatches_ticket_assigned(): void
    {
        Event::fake([TicketAssigned::class, TicketUnassigned::class]);

        $ticket = Ticket::factory()->create(['assignee_id' => null]);
        $agent = User::factory()->create();
        $actor = User::factory()->create();

        $changed = $this->service->applyChanges($ticket, ['assignee_id' => $agent->id], $actor);

        $this->assertContains('assignee_id', $changed);
        $this->assertSame($agent->id, $ticket->fresh()->assignee_id);
        Event::assertDispatched(TicketAssigned::class, fn (TicketAssigned $event) => $event->ticket->is($ticket) && $event->agent->is($agent));
        Event::assertNotDispatched(TicketUnassigned::class);
    }

    public function test_reassigning_a_ticket_to_a_different_agent_dispatches_ticket_assigned(): void
    {
        Event::fake([TicketAssigned::class, TicketUnassigned::class]);

        $firstAgent = User::factory()->create();
        $secondAgent = User::factory()->create();
        $ticket = Ticket::factory()->create(['assignee_id' => $firstAgent->id]);
        $actor = User::factory()->create();

        $this->service->applyChanges($ticket, ['assignee_id' => $secondAgent->id], $actor);

        $this->assertSame($secondAgent->id, $ticket->fresh()->assignee_id);
        Event::assertDispatched(TicketAssigned::class, fn (TicketAssigned $event) => $event->agent->is($secondAgent));
    }

    public function test_unassigning_a_ticket_dispatches_ticket_unassigned(): void
    {
        Event::fake([TicketAssigned::class, TicketUnassigned::class]);

        $agent = User::factory()->create();
        $ticket = Ticket::factory()->create(['assignee_id' => $agent->id]);
        $actor = User::factory()->create();

        $changed = $this->service->applyChanges($ticket, ['assignee_id' => null], $actor);

        $this->assertContains('assignee_id', $changed);
        $fresh = $ticket->fresh();
        $this->assertNull($fresh->assignee_id);
        $this->assertNull($fresh->assigned_at);
        Event::assertDispatched(TicketUnassigned::class, fn (TicketUnassigned $event) => $event->ticket->is($ticket));
        Event::assertNotDispatched(TicketAssigned::class);
    }

    public function test_rejects_an_edit_based_on_an_old_ticket_version(): void
    {
        $ticket = Ticket::factory()->create(['priority' => 'normal']);
        $actor = User::factory()->create();

        $this->expectException(StaleTicketException::class);

        $this->service->applyChanges(
            $ticket,
            ['priority' => 'urgent'],
            $actor,
            now()->subDay()->toIso8601String(),
        );
    }
}
