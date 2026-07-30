<?php

namespace Modules\HelpdeskTickets\Tests\Unit\Services;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Models\Macro;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Services\MacroExecutor;
use Tests\TestCase;

class MacroExecutorTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    private MacroExecutor $executor;

    private TicketStatus $openStatus;

    private int $customerId;

    /** @var list<int> */
    private array $ticketIds = [];

    /** @var list<int> */
    private array $macroIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->executor = new MacroExecutor;

        $this->openStatus = TicketStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );

        $this->customerId = Customer::firstOrCreate(
            ['email' => 'macro-test@example.test'],
            ['name' => 'Macro Test Customer']
        )->id;
    }

    protected function tearDown(): void
    {
        if ($this->ticketIds) {
            Ticket::whereIn('id', $this->ticketIds)->forceDelete();
        }
        if ($this->macroIds) {
            Macro::whereIn('id', $this->macroIds)->delete();
        }

        parent::tearDown();
    }

    public function test_set_priority_action_changes_ticket_priority(): void
    {
        $ticket = $this->createTicket(['priority' => 'low']);

        $macro = Macro::create([
            'name' => 'Escalate priority',
            'actions' => [
                ['type' => 'set_priority', 'value' => 'urgent'],
            ],
            'is_shared' => true,
            'is_active' => true,
            'usage_count' => 0,
        ]);
        $this->macroIds[] = $macro->id;

        $this->executor->run($macro, $ticket);

        $this->assertEquals('urgent', $ticket->fresh()->priority);
    }

    public function test_set_status_action_changes_ticket_status(): void
    {
        $closedStatus = TicketStatus::firstOrCreate(
            ['slug' => 'closed'],
            ['name' => 'Closed', 'color' => '#6c757d', 'is_open' => false, 'is_default' => false, 'order' => 2]
        );

        $ticket = $this->createTicket();

        $macro = Macro::create([
            'name' => 'Close ticket macro',
            'actions' => [
                ['type' => 'set_status', 'value' => $closedStatus->id],
            ],
            'is_shared' => true,
            'is_active' => true,
            'usage_count' => 0,
        ]);
        $this->macroIds[] = $macro->id;

        $this->executor->run($macro, $ticket);

        $this->assertEquals($closedStatus->id, $ticket->fresh()->status_id);
    }

    public function test_assign_user_action_sets_assignee(): void
    {
        $ticket = $this->createTicket(['assignee_id' => null]);
        $agentId = 99;

        $macro = Macro::create([
            'name' => 'Auto-assign macro',
            'actions' => [
                ['type' => 'assign_user', 'value' => $agentId],
            ],
            'is_shared' => true,
            'is_active' => true,
            'usage_count' => 0,
        ]);
        $this->macroIds[] = $macro->id;

        $this->executor->run($macro, $ticket);

        $this->assertEquals($agentId, $ticket->fresh()->assignee_id);
    }

    public function test_usage_count_is_incremented_after_run(): void
    {
        $ticket = $this->createTicket();

        $macro = Macro::create([
            'name' => 'Count test macro',
            'actions' => [
                ['type' => 'set_priority', 'value' => 'normal'],
            ],
            'is_shared' => true,
            'is_active' => true,
            'usage_count' => 7,
        ]);
        $this->macroIds[] = $macro->id;

        $this->executor->run($macro, $ticket);

        $this->assertEquals(8, $macro->fresh()->usage_count);
    }

    public function test_unknown_action_type_does_not_throw(): void
    {
        $ticket = $this->createTicket(['priority' => 'normal']);

        $macro = Macro::create([
            'name' => 'Unknown action macro',
            'actions' => [
                ['type' => 'unknown_action_xyz', 'value' => 'whatever'],
            ],
            'is_shared' => true,
            'is_active' => true,
            'usage_count' => 0,
        ]);
        $this->macroIds[] = $macro->id;

        $this->executor->run($macro, $ticket);

        $this->assertEquals('normal', $ticket->fresh()->priority);
    }

    public function test_multiple_actions_execute_sequentially(): void
    {
        $ticket = $this->createTicket(['priority' => 'low', 'assignee_id' => null]);
        $agentId = 55;

        $macro = Macro::create([
            'name' => 'Multi-action macro',
            'actions' => [
                ['type' => 'set_priority', 'value' => 'high'],
                ['type' => 'assign_user', 'value' => $agentId],
            ],
            'is_shared' => true,
            'is_active' => true,
            'usage_count' => 0,
        ]);
        $this->macroIds[] = $macro->id;

        $this->executor->run($macro, $ticket);

        $fresh = $ticket->fresh();
        $this->assertEquals('high', $fresh->priority);
        $this->assertEquals($agentId, $fresh->assignee_id);
    }

    public function test_last_used_at_is_updated_after_run(): void
    {
        $ticket = $this->createTicket();

        $macro = Macro::create([
            'name' => 'Timestamp test macro',
            'actions' => [
                ['type' => 'set_priority', 'value' => 'normal'],
            ],
            'is_shared' => true,
            'is_active' => true,
            'usage_count' => 0,
            'last_used_at' => null,
        ]);
        $this->macroIds[] = $macro->id;

        $this->executor->run($macro, $ticket);

        $this->assertNotNull($macro->fresh()->last_used_at);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function createTicket(array $overrides = []): Ticket
    {
        $ticket = Ticket::create(array_merge([
            'subject' => 'Macro test ticket',
            'description' => 'Test description.',
            'status_id' => $this->openStatus->id,
            'priority' => 'normal',
            'source' => 'web',
            'customer_id' => $this->customerId,
        ], $overrides));

        $this->ticketIds[] = $ticket->id;

        return $ticket;
    }
}
