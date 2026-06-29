<?php

namespace Modules\HelpdeskTickets\Tests\Unit\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Models\Automation;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Services\AutomationEngine;
use Tests\TestCase;

class AutomationEngineTest extends TestCase
{
    use RefreshDatabase;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    private AutomationEngine $engine;

    private TicketStatus $openStatus;

    private int $customerId;

    /** @var list<int> IDs created during the test run, deleted in tearDown. */
    private array $ticketIds = [];

    /** @var list<int> */
    private array $automationIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->engine = app(AutomationEngine::class);

        $this->openStatus = TicketStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );

        $this->customerId = Customer::firstOrCreate(
            ['email' => 'automation-test@example.test'],
            ['name' => 'Automation Test Customer']
        )->id;
    }

    protected function tearDown(): void
    {
        if ($this->ticketIds) {
            Ticket::whereIn('id', $this->ticketIds)->forceDelete();
        }
        if ($this->automationIds) {
            Automation::whereIn('id', $this->automationIds)->delete();
        }

        parent::tearDown();
    }

    public function test_automation_runs_when_all_conditions_match(): void
    {
        $ticket = $this->createTicket(['priority' => 'urgent']);

        $automation = Automation::create([
            'name' => 'Urgent priority automation',
            'trigger_event' => 'ticket.created',
            'conditions' => [
                ['field' => 'priority', 'op' => 'equals', 'value' => 'urgent'],
            ],
            'actions' => [
                ['type' => 'set_priority', 'value' => 'high'],
            ],
            'order' => 1,
            'is_active' => true,
            'run_count' => 0,
        ]);
        $this->automationIds[] = $automation->id;

        $this->engine->handle('ticket.created', $ticket);

        $this->assertEquals('high', $ticket->fresh()->priority);
    }

    public function test_automation_skipped_when_condition_does_not_match(): void
    {
        $ticket = $this->createTicket(['priority' => 'low']);

        $automation = Automation::create([
            'name' => 'Only urgent automation',
            'trigger_event' => 'ticket.created',
            'conditions' => [
                ['field' => 'priority', 'op' => 'equals', 'value' => 'urgent'],
            ],
            'actions' => [
                ['type' => 'set_priority', 'value' => 'high'],
            ],
            'order' => 1,
            'is_active' => true,
            'run_count' => 0,
        ]);
        $this->automationIds[] = $automation->id;

        $this->engine->handle('ticket.created', $ticket);

        $this->assertEquals('low', $ticket->fresh()->priority);
    }

    public function test_contains_operator_matches_substring(): void
    {
        $ticket = $this->createTicket(['subject' => 'Cannot login to portal']);

        $automation = Automation::create([
            'name' => 'Login issue automation',
            'trigger_event' => 'ticket.created',
            'conditions' => [
                ['field' => 'subject', 'op' => 'contains', 'value' => 'login'],
            ],
            'actions' => [
                ['type' => 'set_priority', 'value' => 'high'],
            ],
            'order' => 1,
            'is_active' => true,
            'run_count' => 0,
        ]);
        $this->automationIds[] = $automation->id;

        $this->engine->handle('ticket.created', $ticket);

        $this->assertEquals('high', $ticket->fresh()->priority);
    }

    public function test_is_null_operator_matches_null_field(): void
    {
        $ticket = $this->createTicket(['assignee_id' => null]);

        $automation = Automation::create([
            'name' => 'Unassigned ticket automation',
            'trigger_event' => 'ticket.created',
            'conditions' => [
                ['field' => 'assignee_id', 'op' => 'is_null', 'value' => null],
            ],
            'actions' => [
                ['type' => 'set_priority', 'value' => 'urgent'],
            ],
            'order' => 1,
            'is_active' => true,
            'run_count' => 0,
        ]);
        $this->automationIds[] = $automation->id;

        $this->engine->handle('ticket.created', $ticket);

        $this->assertEquals('urgent', $ticket->fresh()->priority);
    }

    public function test_set_priority_action_changes_ticket_priority(): void
    {
        $ticket = $this->createTicket(['priority' => 'low']);

        $automation = Automation::create([
            'name' => 'Escalate all low tickets',
            'trigger_event' => 'ticket.created',
            'conditions' => [],
            'actions' => [
                ['type' => 'set_priority', 'value' => 'normal'],
            ],
            'order' => 1,
            'is_active' => true,
            'run_count' => 0,
        ]);
        $this->automationIds[] = $automation->id;

        $this->engine->handle('ticket.created', $ticket);

        $this->assertEquals('normal', $ticket->fresh()->priority);
    }

    public function test_assign_user_action_sets_assignee(): void
    {
        $ticket = $this->createTicket(['assignee_id' => null]);
        $agentId = 999;

        $automation = Automation::create([
            'name' => 'Auto-assign ticket',
            'trigger_event' => 'ticket.created',
            'conditions' => [],
            'actions' => [
                ['type' => 'assign_user', 'value' => $agentId],
            ],
            'order' => 1,
            'is_active' => true,
            'run_count' => 0,
        ]);
        $this->automationIds[] = $automation->id;

        $this->engine->handle('ticket.created', $ticket);

        $this->assertEquals($agentId, $ticket->fresh()->assignee_id);
    }

    public function test_inactive_automation_is_ignored(): void
    {
        $ticket = $this->createTicket(['priority' => 'low']);

        $automation = Automation::create([
            'name' => 'Inactive automation',
            'trigger_event' => 'ticket.created',
            'conditions' => [],
            'actions' => [
                ['type' => 'set_priority', 'value' => 'urgent'],
            ],
            'order' => 1,
            'is_active' => false,
            'run_count' => 0,
        ]);
        $this->automationIds[] = $automation->id;

        $this->engine->handle('ticket.created', $ticket);

        $this->assertEquals('low', $ticket->fresh()->priority);
    }

    public function test_run_count_is_incremented_after_execution(): void
    {
        $ticket = $this->createTicket();

        $automation = Automation::create([
            'name' => 'Count increment test',
            'trigger_event' => 'ticket.created',
            'conditions' => [],
            'actions' => [
                ['type' => 'set_priority', 'value' => 'normal'],
            ],
            'order' => 1,
            'is_active' => true,
            'run_count' => 5,
        ]);
        $this->automationIds[] = $automation->id;

        $this->engine->handle('ticket.created', $ticket);

        $this->assertEquals(6, $automation->fresh()->run_count);
    }

    public function test_wrong_event_does_not_trigger_automation(): void
    {
        $ticket = $this->createTicket(['priority' => 'low']);

        $automation = Automation::create([
            'name' => 'Only on update',
            'trigger_event' => 'ticket.updated',
            'conditions' => [],
            'actions' => [
                ['type' => 'set_priority', 'value' => 'urgent'],
            ],
            'order' => 1,
            'is_active' => true,
            'run_count' => 0,
        ]);
        $this->automationIds[] = $automation->id;

        $this->engine->handle('ticket.created', $ticket);

        $this->assertEquals('low', $ticket->fresh()->priority);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function createTicket(array $overrides = []): Ticket
    {
        $ticket = Ticket::create(array_merge([
            'subject' => 'Automation test ticket',
            'description' => 'Test description for automation.',
            'status_id' => $this->openStatus->id,
            'priority' => 'normal',
            'source' => 'web',
            'customer_id' => $this->customerId,
        ], $overrides));

        $this->ticketIds[] = $ticket->id;

        return $ticket;
    }
}
