<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * linkTicket must authorize update on the source ticket AND view on the
 * target ticket (it used to create the link without any policy check).
 */
class TicketLinkAuthorizationTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    private TicketStatus $status;

    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->helpdeskConnectionAvailable()) {
            $this->markTestSkipped('Helpdesk database connection is not available.');
        }

        Permission::firstOrCreate(['name' => 'helpdesk.tickets.update', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'helpdesk.tickets.view', 'guard_name' => 'web']);

        // The manager routes sit behind role:super-admin|super-settings, but
        // both roles bypass ALL policies via Gate::before hooks, which would
        // make the per-ticket authorization untestable. Skip the role
        // middleware and test the controller/policy layer with users that only
        // hold the explicitly granted permissions.
        $this->withoutMiddleware(RoleMiddleware::class);

        $this->status = TicketStatus::firstOrCreate(
            ['slug' => 'open'],
            [
                'name' => 'Open',
                'color' => '#13C672',
                'is_open' => true,
                'is_default' => true,
                'order' => 1,
            ]
        );
    }

    public function test_user_with_update_and_view_permissions_can_link(): void
    {
        $user = $this->makeUser(['helpdesk.tickets.update', 'helpdesk.tickets.view']);
        $source = $this->createTicket();
        $target = $this->createTicket();

        $this->actingAs($user)
            ->post(route('manager.helpdesk.tickets.link', $source), [
                'linked_ticket_id' => $target->id,
                'link_type' => 'related',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('helpdesk_ticket_links', [
            'ticket_id' => $source->id,
            'linked_ticket_id' => $target->id,
        ], 'helpdesk');
    }

    public function test_user_without_update_permission_cannot_link(): void
    {
        // Passes the role route middleware but holds no ticket permissions.
        $user = $this->makeUser([]);
        $source = $this->createTicket();
        $target = $this->createTicket();

        $this->actingAs($user)
            ->postJson(route('manager.helpdesk.tickets.link', $source), [
                'linked_ticket_id' => $target->id,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('helpdesk_ticket_links', [
            'ticket_id' => $source->id,
            'linked_ticket_id' => $target->id,
        ], 'helpdesk');
    }

    public function test_user_who_cannot_view_target_ticket_cannot_link(): void
    {
        // Can update (global permission) but cannot view the target ticket.
        $user = $this->makeUser(['helpdesk.tickets.update']);
        $source = $this->createTicket();
        $target = $this->createTicket();

        $this->actingAs($user)
            ->postJson(route('manager.helpdesk.tickets.link', $source), [
                'linked_ticket_id' => $target->id,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('helpdesk_ticket_links', [
            'ticket_id' => $source->id,
            'linked_ticket_id' => $target->id,
        ], 'helpdesk');
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    /**
     * @param  array<int, string>  $permissions
     */
    private function makeUser(array $permissions): User
    {
        $user = User::factory()->create();

        if ($permissions !== []) {
            $user->givePermissionTo($permissions);
        }

        return $user;
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createTicket(array $overrides = []): Ticket
    {
        return Ticket::create(array_merge([
            'subject' => 'Link test ticket',
            'description' => 'Test description.',
            'status_id' => $this->status->id,
            'priority' => 'normal',
            'source' => 'web',
        ], $overrides));
    }

    private function helpdeskConnectionAvailable(): bool
    {
        try {
            DB::connection('helpdesk')->getPdo();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
