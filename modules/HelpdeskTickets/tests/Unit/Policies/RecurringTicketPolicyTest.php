<?php

namespace Modules\HelpdeskTickets\Tests\Unit\Policies;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\HelpdeskTickets\Models\RecurringTicket;
use Modules\HelpdeskTickets\Policies\RecurringTicketPolicy;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RecurringTicketPolicyTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private RecurringTicketPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new RecurringTicketPolicy;

        Permission::firstOrCreate(['name' => 'helpdesk.tickets.manage', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
    }

    public function test_user_without_manage_permission_cannot_view_create_or_update(): void
    {
        $user = User::factory()->create();
        $recurring = RecurringTicket::factory()->create();

        $this->assertFalse($this->policy->viewAny($user));
        $this->assertFalse($this->policy->create($user));
        $this->assertFalse($this->policy->update($user, $recurring));
    }

    public function test_user_with_manage_permission_can_view_create_and_update(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('helpdesk.tickets.manage');
        $recurring = RecurringTicket::factory()->create();

        $this->assertTrue($this->policy->viewAny($user));
        $this->assertTrue($this->policy->create($user));
        $this->assertTrue($this->policy->update($user, $recurring));
    }

    public function test_delete_is_restricted_to_super_admin_even_with_manage_permission(): void
    {
        $manager = User::factory()->create();
        $manager->givePermissionTo('helpdesk.tickets.manage');
        $recurring = RecurringTicket::factory()->create();

        $this->assertFalse($this->policy->delete($manager, $recurring));
    }

    public function test_super_admin_can_delete(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');
        $recurring = RecurringTicket::factory()->create();

        $this->assertTrue($this->policy->delete($superAdmin, $recurring));
    }
}
