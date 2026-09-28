<?php

namespace Modules\HelpdeskTickets\Tests\Unit\Policies;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\HelpdeskTickets\Models\TicketView;
use Modules\HelpdeskTickets\Policies\TicketViewPolicy;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class TicketViewPolicyTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private TicketViewPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new TicketViewPolicy;

        Permission::firstOrCreate(['name' => 'helpdesk.tickets.settings', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'helpdesk.tickets.manage', 'guard_name' => 'web']);
    }

    public function test_user_without_settings_permission_is_denied_view_any_and_create(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($this->policy->viewAny($user));
        $this->assertFalse($this->policy->create($user));
    }

    public function test_user_with_settings_permission_is_allowed_view_any_and_create(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('helpdesk.tickets.settings');

        $this->assertTrue($this->policy->viewAny($user));
        $this->assertTrue($this->policy->create($user));
    }

    public function test_owner_can_view_update_and_delete_their_own_view(): void
    {
        $owner = User::factory()->create();
        $view = TicketView::factory()->create(['user_id' => $owner->id]);

        $this->assertTrue($this->policy->view($owner, $view));
        $this->assertTrue($this->policy->update($owner, $view));
        $this->assertTrue($this->policy->delete($owner, $view));
    }

    public function test_other_agent_cannot_touch_someone_elses_view(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $view = TicketView::factory()->create(['user_id' => $owner->id]);

        $this->assertFalse($this->policy->view($other, $view));
        $this->assertFalse($this->policy->update($other, $view));
        $this->assertFalse($this->policy->delete($other, $view));
    }

    public function test_admin_with_settings_permission_can_manage_anyones_view(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $admin->givePermissionTo('helpdesk.tickets.settings');
        $view = TicketView::factory()->create(['user_id' => $owner->id]);

        $this->assertTrue($this->policy->view($admin, $view));
        $this->assertTrue($this->policy->update($admin, $view));
        $this->assertTrue($this->policy->delete($admin, $view));
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
