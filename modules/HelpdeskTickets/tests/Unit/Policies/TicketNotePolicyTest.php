<?php

namespace Modules\HelpdeskTickets\Tests\Unit\Policies;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketNote;
use Modules\HelpdeskTickets\Policies\TicketNotePolicy;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TicketNotePolicyTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private TicketNotePolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new TicketNotePolicy;

        Permission::firstOrCreate(['name' => 'helpdesk.tickets.manage', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'helpdesk.tickets.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'helpdesk.tickets.update', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
    }

    private function noteBy(User $author): TicketNote
    {
        $ticket = Ticket::factory()->create();

        return TicketNote::factory()->create([
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

    public function test_manager_can_view_any_note(): void
    {
        $manager = User::factory()->create();
        $manager->givePermissionTo('helpdesk.tickets.manage');
        $note = $this->noteBy(User::factory()->create());

        $this->assertTrue($this->policy->view($manager, $note));
    }

    public function test_author_can_view_their_own_note_without_permission(): void
    {
        $author = User::factory()->create();
        $note = $this->noteBy($author);

        $this->assertTrue($this->policy->view($author, $note));
    }

    public function test_other_agent_without_manage_cannot_view_someone_elses_note(): void
    {
        $author = User::factory()->create();
        $other = User::factory()->create();
        $note = $this->noteBy($author);

        $this->assertFalse($this->policy->view($other, $note));
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
        $note = $this->noteBy($author);

        $this->assertTrue($this->policy->update($author, $note));
        $this->assertTrue($this->policy->delete($author, $note));
        $this->assertTrue($this->policy->restore($author, $note));

        $this->assertFalse($this->policy->update($other, $note));
        $this->assertFalse($this->policy->delete($other, $note));
        $this->assertFalse($this->policy->restore($other, $note));

        $this->assertTrue($this->policy->update($superAdmin, $note));
        $this->assertTrue($this->policy->delete($superAdmin, $note));
        $this->assertTrue($this->policy->restore($superAdmin, $note));
    }
}
