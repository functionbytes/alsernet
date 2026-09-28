<?php

namespace Modules\HelpdeskTickets\Tests\Unit\Policies;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\HelpdeskTickets\Models\TicketCategory;
use Modules\HelpdeskTickets\Policies\TicketCategoryPolicy;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class TicketCategoryPolicyTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private TicketCategoryPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new TicketCategoryPolicy;

        Permission::firstOrCreate(['name' => 'helpdesk.tickets.settings', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'helpdesk.tickets.manage', 'guard_name' => 'web']);
    }

    public function test_user_without_settings_permission_is_denied_every_ability(): void
    {
        $user = User::factory()->create();
        $category = TicketCategory::factory()->create();

        $this->assertFalse($this->policy->viewAny($user));
        $this->assertFalse($this->policy->view($user, $category));
        $this->assertFalse($this->policy->create($user));
        $this->assertFalse($this->policy->update($user, $category));
        $this->assertFalse($this->policy->delete($user, $category));
    }

    public function test_user_with_settings_permission_is_allowed_every_ability(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('helpdesk.tickets.settings');
        $category = TicketCategory::factory()->create();

        $this->assertTrue($this->policy->viewAny($user));
        $this->assertTrue($this->policy->view($user, $category));
        $this->assertTrue($this->policy->create($user));
        $this->assertTrue($this->policy->update($user, $category));
        $this->assertTrue($this->policy->delete($user, $category));
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
