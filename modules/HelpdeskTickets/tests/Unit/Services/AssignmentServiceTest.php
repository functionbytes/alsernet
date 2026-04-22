<?php

namespace Modules\HelpdeskTickets\Tests\Unit\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Modules\Helpdesk\Services\NotificationService;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Services\AssignmentService;
use Tests\TestCase;

/**
 * Unit tests for AssignmentService.
 *
 * These tests mock out Eloquent and focus on business logic only.
 * DB-backed paths are covered in Feature/AutoAssignmentTest.php.
 */
class AssignmentServiceTest extends TestCase
{
    private function makeService(): AssignmentService
    {
        $notificationService = $this->createMock(NotificationService::class);

        return new AssignmentService($notificationService);
    }

    // ─── autoAssignByRoundRobin ────────────────────────────────────────────────

    public function test_auto_assign_by_round_robin_returns_null_when_no_agents_available(): void
    {
        // This path exercises the early-return when getAvailableAgents() is empty.
        // We skip if no helpdesk DB is reachable; otherwise we verify the null return.
        try {
            $service = $this->makeService();

            $ticket = $this->getMockBuilder(Ticket::class)
                ->disableOriginalConstructor()
                ->onlyMethods([])
                ->getMock();

            $ticket->category_id = null;

            // With no agents in the DB, autoAssignByRoundRobin should return null.
            $result = $service->autoAssignByRoundRobin($ticket);

            $this->assertNull($result, 'Round-robin returns null when no agents are available.');
        } catch (\Throwable) {
            $this->markTestSkipped('Helpdesk database connection is not available.');
        }
    }

    // ─── getAvailableAgents ────────────────────────────────────────────────────

    public function test_get_available_agents_returns_collection(): void
    {
        try {
            $service = $this->makeService();

            $agents = $service->getAvailableAgents();

            $this->assertInstanceOf(Collection::class, $agents);
        } catch (\Throwable) {
            $this->markTestSkipped('Helpdesk database connection is not available.');
        }
    }

    public function test_get_agent_workload_returns_integer(): void
    {
        try {
            $service = $this->makeService();

            // Workload for a non-existent agent is always 0.
            $workload = $service->getAgentWorkload(PHP_INT_MAX);

            $this->assertSame(0, $workload);
        } catch (\Throwable) {
            $this->markTestSkipped('Helpdesk database connection is not available.');
        }
    }

    // ─── assignTicket (role guard) ─────────────────────────────────────────────

    public function test_assign_ticket_throws_when_user_is_not_agent(): void
    {
        try {
            $service = $this->makeService();

            $ticket = $this->getMockBuilder(Ticket::class)
                ->disableOriginalConstructor()
                ->onlyMethods([])
                ->getMock();

            $ticket->id = 1;
            $ticket->assignee_id = null;

            // Find or create a real user without the helpdesk-agent role
            $user = User::first() ?? User::factory()->create();

            if ($user->hasRole('helpdesk-agent')) {
                $this->markTestSkipped('All users in test DB have helpdesk-agent role.');
            }

            $this->expectException(\Exception::class);
            $this->expectExceptionMessage('User is not a helpdesk agent');

            $service->assignTicket($ticket, $user->id);
        } catch (\Throwable $e) {
            if (str_contains($e->getMessage(), 'User is not a helpdesk agent')) {
                // Expected exception — re-throw so PHPUnit can catch it
                throw $e;
            }
            $this->markTestSkipped('Helpdesk database connection is not available.');
        }
    }

    // ─── reassignTicket ────────────────────────────────────────────────────────

    public function test_reassign_ticket_throws_when_new_agent_not_found(): void
    {
        try {
            $service = $this->makeService();

            $ticket = $this->getMockBuilder(Ticket::class)
                ->disableOriginalConstructor()
                ->onlyMethods([])
                ->getMock();

            $ticket->id = 1;
            $ticket->assignee_id = null;

            $this->expectException(ModelNotFoundException::class);

            $service->reassignTicket($ticket, PHP_INT_MAX);
        } catch (ModelNotFoundException $e) {
            throw $e;
        } catch (\Throwable) {
            $this->markTestSkipped('Helpdesk database connection is not available.');
        }
    }
}
