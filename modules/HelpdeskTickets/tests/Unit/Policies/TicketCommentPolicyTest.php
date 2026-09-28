<?php

namespace Modules\HelpdeskTickets\Tests\Unit\Policies;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketComment;
use Modules\HelpdeskTickets\Policies\TicketCommentPolicy;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TicketCommentPolicyTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private TicketCommentPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new TicketCommentPolicy;

        Permission::firstOrCreate(['name' => 'helpdesk.tickets.manage', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'helpdesk.tickets.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'helpdesk.tickets.update', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
    }

    private function commentBy(User $author): TicketComment
    {
        $ticket = Ticket::factory()->create();

        return TicketComment::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $author->id,
        ]);
    }

    public function test_view_any_requires_manage_or_view_permission(): void
    {
        $noPermission = User::factory()->create();
        $withView = User::factory()->create();
        $withView->givePermissionTo('helpdesk.tickets.view');

        $this->assertFalse($this->policy->viewAny($noPermission));
        $this->assertTrue($this->policy->viewAny($withView));
    }

    public function test_manager_can_view_any_comment(): void
    {
        $manager = User::factory()->create();
        $manager->givePermissionTo('helpdesk.tickets.manage');
        $comment = $this->commentBy(User::factory()->create());

        $this->assertTrue($this->policy->view($manager, $comment));
    }

    public function test_author_can_view_their_own_comment_without_permission(): void
    {
        $author = User::factory()->create();
        $comment = $this->commentBy($author);

        $this->assertTrue($this->policy->view($author, $comment));
    }

    public function test_other_agent_without_manage_cannot_view_someone_elses_comment(): void
    {
        $author = User::factory()->create();
        $other = User::factory()->create();
        $comment = $this->commentBy($author);

        $this->assertFalse($this->policy->view($other, $comment));
    }

    public function test_create_requires_update_or_manage_permission(): void
    {
        $noPermission = User::factory()->create();
        $withUpdate = User::factory()->create();
        $withUpdate->givePermissionTo('helpdesk.tickets.update');

        $this->assertFalse($this->policy->create($noPermission));
        $this->assertTrue($this->policy->create($withUpdate));
    }

    public function test_only_author_or_super_admin_can_update_delete_or_restore(): void
    {
        $author = User::factory()->create();
        $other = User::factory()->create();
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');
        $comment = $this->commentBy($author);

        $this->assertTrue($this->policy->update($author, $comment));
        $this->assertTrue($this->policy->delete($author, $comment));
        $this->assertTrue($this->policy->restore($author, $comment));

        $this->assertFalse($this->policy->update($other, $comment));
        $this->assertFalse($this->policy->delete($other, $comment));
        $this->assertFalse($this->policy->restore($other, $comment));

        $this->assertTrue($this->policy->update($superAdmin, $comment));
        $this->assertTrue($this->policy->delete($superAdmin, $comment));
        $this->assertTrue($this->policy->restore($superAdmin, $comment));
    }
}
