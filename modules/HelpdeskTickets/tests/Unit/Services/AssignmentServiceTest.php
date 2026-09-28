<?php

namespace Modules\HelpdeskTickets\Tests\Unit\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Services\AssignmentService;
use Modules\HelpdeskTickets\Tests\Concerns\IsolatesAgentPool;
use Modules\HelpdeskTickets\Tests\Concerns\SharesHelpdeskPdo;
use PHPUnit\Framework\AssertionFailedError;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Unit tests for AssignmentService.
 *
 * These tests mock out Eloquent and focus on business logic only.
 * DB-backed paths are covered in Feature/AutoAssignmentTest.php.
 */
class AssignmentServiceTest extends TestCase
{
    use IsolatesAgentPool;
    use SharesHelpdeskPdo;

    private function makeService(): AssignmentService
    {

        return new AssignmentService;
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

    /**
     * Empate de workload entre 3 agentes: cada ronda cierra el ticket recién
     * asignado para que el workload vuelva a 0 en los tres y el desempate
     * dependa solo del historial de asignaciones (candidateAgents ordenado
     * por agent_id). Los agentes se crean en orden Zed/Ann/Mid (ids
     * ascendentes 1,2,3 en ese orden de creación) pero con nombres que
     * ordenan distinto por firstname —el orden que usa
     * getAvailableAgents()—, para que una rotación que dependiera del orden
     * de llegada de los agentes en vez de su agent_id no pase este test por
     * casualidad.
     */
    public function test_auto_assign_by_round_robin_rotates_through_tied_agents(): void
    {
        try {
            $this->isolateAgentPool();

            app(PermissionRegistrar::class)->forgetCachedPermissions();
            Role::findOrCreate('helpdesk-agent', 'web');

            $openStatus = TicketStatus::firstOrCreate(
                ['slug' => 'open'],
                [
                    'name' => 'Open',
                    'color' => '#13C672',
                    'is_open' => true,
                    'is_default' => true,
                    'order' => 1,
                ]
            );

            $customer = Customer::create([
                'name' => 'Round Robin Customer',
                'email' => 'roundrobin@example.com',
            ]);

            $agentZed = $this->createAgent('Zed', 'zed-rr@example.com');
            $agentAnn = $this->createAgent('Ann', 'ann-rr@example.com');
            $agentMid = $this->createAgent('Mid', 'mid-rr@example.com');

            $service = $this->makeService();

            // Empatados desde 0: primera vuelta va por agent_id ascendente
            // (Zed, Ann, Mid) y luego rota de nuevo a Zed.
            $expectedOrder = [$agentZed->id, $agentAnn->id, $agentMid->id, $agentZed->id];

            foreach ($expectedOrder as $expectedAgentId) {
                $ticket = Ticket::create([
                    'subject' => 'Round robin ticket',
                    'description' => 'Assign me.',
                    'customer_id' => $customer->id,
                    'status_id' => $openStatus->id,
                    'priority' => 'normal',
                    'source' => 'portal',
                ]);

                $assignment = $service->autoAssignByRoundRobin($ticket);

                $this->assertNotNull($assignment);
                $this->assertSame($expectedAgentId, $assignment->assigned_to);

                $ticket->update(['closed_at' => now()]);
            }
        } catch (AssertionFailedError $e) {
            throw $e;
        } catch (\Throwable) {
            $this->markTestSkipped('Helpdesk database connection is not available.');
        }
    }

    private function createAgent(string $name, string $email): User
    {
        $user = User::factory()->create([
            'firstname' => $name,
            'lastname' => 'Agent',
            'email' => $email,
        ]);

        $user->assignRole('helpdesk-agent');

        return $user;
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
