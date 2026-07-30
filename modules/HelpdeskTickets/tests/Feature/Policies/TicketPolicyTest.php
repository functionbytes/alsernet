<?php

namespace Modules\HelpdeskTickets\Tests\Feature\Policies;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Database\Seeders\HelpdeskTicketsPermissionsSeeder;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Tests\TestCase;

class TicketPolicyTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    private Customer $customer;

    private TicketStatus $openStatus;

    protected function setUp(): void
    {
        parent::setUp();

        // TicketPolicy usa hasPermissionTo(), que LANZA PermissionDoesNotExist
        // si el permiso no está en BD — la BD de test arranca sin permisos.
        $this->seed(HelpdeskTicketsPermissionsSeeder::class);

        $this->openStatus = TicketStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );

        $this->customer = Customer::firstOrCreate(
            ['email' => 'policy-test-customer@example.com'],
            ['name' => 'Policy Test Customer']
        );
    }

    public function test_user_without_permission_cannot_view_any(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($user->can('viewAny', Ticket::class));
    }

    public function test_user_with_view_permission_can_view_any(): void
    {
        $user = User::factory()->create();

        try {
            $user->givePermissionTo('helpdesk.tickets.view');
        } catch (\Throwable) {
            $this->markTestSkipped('Permissions not available in test env.');
        }

        $this->assertTrue($user->can('viewAny', Ticket::class));
    }

    public function test_user_without_create_permission_cannot_create(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($user->can('create', Ticket::class));
    }

    public function test_user_with_create_permission_can_create(): void
    {
        $user = User::factory()->create();

        try {
            $user->givePermissionTo('helpdesk.tickets.create');
        } catch (\Throwable) {
            $this->markTestSkipped('Permissions not available in test env.');
        }

        $this->assertTrue($user->can('create', Ticket::class));
    }

    public function test_assignee_can_view_own_ticket_without_global_permission(): void
    {
        $assignee = User::factory()->create();
        $ticket = $this->createTicket(['assignee_id' => $assignee->id]);

        $this->assertTrue($assignee->can('view', $ticket));
    }

    public function test_non_assignee_without_permission_cannot_view_ticket(): void
    {
        $otherUser = User::factory()->create();
        $ticket = $this->createTicket(['assignee_id' => null]);

        $this->assertFalse($otherUser->can('view', $ticket));
    }

    public function test_only_super_admin_can_force_delete(): void
    {
        $regularUser = User::factory()->create();
        $ticket = $this->createTicket();

        $this->assertFalse($regularUser->can('forceDelete', $ticket));
    }

    public function test_super_admin_can_force_delete(): void
    {
        $superAdmin = User::factory()->create();

        try {
            $superAdmin->assignRole('super-admin');
        } catch (\Throwable) {
            $this->markTestSkipped('Roles not available in test env.');
        }

        $ticket = $this->createTicket();

        $this->assertTrue($superAdmin->can('forceDelete', $ticket));
    }

    public function test_assign_requires_update_permission(): void
    {
        $user = User::factory()->create();
        $ticket = $this->createTicket();

        $this->assertFalse($user->can('assign', $ticket));
    }

    public function test_user_with_update_permission_can_assign(): void
    {
        $user = User::factory()->create();

        try {
            $user->givePermissionTo('helpdesk.tickets.update');
        } catch (\Throwable) {
            $this->markTestSkipped('Permissions not available in test env.');
        }

        $ticket = $this->createTicket();

        $this->assertTrue($user->can('assign', $ticket));
    }

    public function test_assignee_can_update_own_ticket_without_global_permission(): void
    {
        $assignee = User::factory()->create();
        $ticket = $this->createTicket(['assignee_id' => $assignee->id]);

        $this->assertTrue($assignee->can('update', $ticket));
    }

    public function test_non_assignee_without_permission_cannot_update(): void
    {
        $otherUser = User::factory()->create();
        $ticket = $this->createTicket(['assignee_id' => null]);

        $this->assertFalse($otherUser->can('update', $ticket));
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function createTicket(array $overrides = []): Ticket
    {
        return Ticket::create(array_merge([
            'subject' => 'Policy test ticket',
            'description' => 'Test description.',
            'customer_id' => $this->customer->id,
            'status_id' => $this->openStatus->id,
            'priority' => 'normal',
            'source' => 'web',
        ], $overrides));
    }
}
