<?php

namespace Modules\HelpdeskTickets\Tests\Unit\Policies;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketTimeEntry;
use Modules\HelpdeskTickets\Policies\TimeEntryPolicy;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TimeEntryPolicyTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private TimeEntryPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new TimeEntryPolicy;

        Permission::firstOrCreate(['name' => 'helpdesk.tickets.manage', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'helpdesk.tickets.update', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
    }

    public function test_create_requires_update_or_manage_permission(): void
    {
        $noPermission = User::factory()->create();
        $withUpdate = User::factory()->create();
        $withUpdate->givePermissionTo('helpdesk.tickets.update');
        $withManage = User::factory()->create();
        $withManage->givePermissionTo('helpdesk.tickets.manage');

        $this->assertFalse($this->policy->create($noPermission));
        $this->assertTrue($this->policy->create($withUpdate));
        $this->assertTrue($this->policy->create($withManage));
    }

    public function test_only_logger_or_super_admin_can_delete(): void
    {
        $logger = User::factory()->create();
        $other = User::factory()->create();
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');

        $ticket = Ticket::factory()->create();
        $entry = TicketTimeEntry::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $logger->id,
        ]);

        $this->assertTrue($this->policy->delete($logger, $entry));
        $this->assertFalse($this->policy->delete($other, $entry));
        $this->assertTrue($this->policy->delete($superAdmin, $entry));
    }
}
