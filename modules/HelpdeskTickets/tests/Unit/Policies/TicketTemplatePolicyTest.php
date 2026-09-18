<?php

namespace Modules\HelpdeskTickets\Tests\Unit\Policies;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\HelpdeskTickets\Models\TicketTemplate;
use Modules\HelpdeskTickets\Policies\TicketTemplatePolicy;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class TicketTemplatePolicyTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    private TicketTemplatePolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new TicketTemplatePolicy;

        Permission::firstOrCreate(['name' => 'helpdesk.tickets.manage', 'guard_name' => 'web']);
    }

    public function test_anyone_can_create_a_template(): void
    {
        $agent = User::factory()->create();

        $this->assertTrue($this->policy->create($agent));
    }

    public function test_owner_can_update_their_own_personal_template(): void
    {
        $owner = User::factory()->create();
        $template = TicketTemplate::factory()->ownedBy($owner->id)->create();

        $this->assertTrue($this->policy->update($owner, $template));
    }

    public function test_other_agent_cannot_update_someone_elses_personal_template(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $template = TicketTemplate::factory()->ownedBy($owner->id)->create();

        $this->assertFalse($this->policy->update($other, $template));
    }

    public function test_agent_without_manage_permission_cannot_update_a_general_template(): void
    {
        $agent = User::factory()->create();
        $template = TicketTemplate::factory()->create();

        $this->assertFalse($this->policy->update($agent, $template));
    }

    public function test_admin_with_manage_permission_can_update_a_general_template(): void
    {
        $admin = User::factory()->create();
        $admin->givePermissionTo('helpdesk.tickets.manage');
        $template = TicketTemplate::factory()->create();

        $this->assertTrue($this->policy->update($admin, $template));
    }

    public function test_delete_follows_the_same_rules_as_update(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $template = TicketTemplate::factory()->ownedBy($owner->id)->create();

        $this->assertTrue($this->policy->delete($owner, $template));
        $this->assertFalse($this->policy->delete($other, $template));
    }
}
