<?php

namespace Modules\HelpdeskTickets\Tests\Unit\Policies;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\HelpdeskTickets\Models\TicketCannedReply;
use Modules\HelpdeskTickets\Policies\TicketCannedReplyPolicy;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class TicketCannedReplyPolicyTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private TicketCannedReplyPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new TicketCannedReplyPolicy;

        Permission::firstOrCreate(['name' => 'helpdesk.tickets.settings', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'helpdesk.tickets.manage', 'guard_name' => 'web']);
    }

    public function test_create_requires_the_settings_permission(): void
    {
        $agent = User::factory()->create();
        $admin = User::factory()->create();
        $admin->givePermissionTo('helpdesk.tickets.settings');

        $this->assertFalse($this->policy->create($agent));
        $this->assertTrue($this->policy->create($admin));
    }

    public function test_owner_can_view_update_and_delete_their_own_personal_reply(): void
    {
        $owner = User::factory()->create();
        $reply = TicketCannedReply::factory()->personal($owner->id)->create();

        $this->assertTrue($this->policy->view($owner, $reply));
        $this->assertTrue($this->policy->update($owner, $reply));
        $this->assertTrue($this->policy->delete($owner, $reply));
    }

    public function test_other_agent_cannot_touch_someone_elses_personal_reply(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $reply = TicketCannedReply::factory()->personal($owner->id)->create();

        $this->assertFalse($this->policy->view($other, $reply));
        $this->assertFalse($this->policy->update($other, $reply));
        $this->assertFalse($this->policy->delete($other, $reply));
    }

    public function test_agent_without_settings_permission_cannot_touch_a_global_reply(): void
    {
        $agent = User::factory()->create();
        $reply = TicketCannedReply::factory()->create();

        $this->assertFalse($this->policy->update($agent, $reply));
        $this->assertFalse($this->policy->delete($agent, $reply));
    }

    public function test_admin_with_settings_permission_can_manage_a_global_reply(): void
    {
        $admin = User::factory()->create();
        $admin->givePermissionTo('helpdesk.tickets.settings');
        $reply = TicketCannedReply::factory()->create();

        $this->assertTrue($this->policy->view($admin, $reply));
        $this->assertTrue($this->policy->update($admin, $reply));
        $this->assertTrue($this->policy->delete($admin, $reply));
    }

    public function test_manage_requires_the_manage_permission_specifically(): void
    {
        $withSettingsOnly = User::factory()->create();
        $withSettingsOnly->givePermissionTo('helpdesk.tickets.settings');

        $withManage = User::factory()->create();
        $withManage->givePermissionTo('helpdesk.tickets.manage');

        $this->assertFalse($this->policy->manage($withSettingsOnly));
        $this->assertTrue($this->policy->manage($withManage));
    }
}
