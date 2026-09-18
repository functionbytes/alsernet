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
 * bulkReply must authorize per ticket via the policy (not only a global
 * permission) and create the messages through the model so observers,
 * events and timestamps behave exactly like a single storeMessage.
 */
class BulkReplyTest extends TestCase
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

    public function test_manager_with_permissions_can_bulk_reply(): void
    {
        $user = $this->makeUser(['helpdesk.tickets.view', 'helpdesk.tickets.update']);
        $ticketA = $this->createTicket();
        $ticketB = $this->createTicket();

        $response = $this->actingAs($user)
            ->postJson(route('manager.helpdesk.tickets.bulk-reply'), [
                'ticket_ids' => [$ticketA->id, $ticketB->id],
                'body' => 'Respuesta masiva de prueba',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'replied_ticket_ids')
            ->assertJsonCount(0, 'skipped_ticket_ids');

        foreach ([$ticketA, $ticketB] as $ticket) {
            $this->assertDatabaseHas('helpdesk_ticket_items', [
                'ticket_id' => $ticket->id,
                'type' => 'message',
                'user_id' => $user->id,
                'body' => 'Respuesta masiva de prueba',
            ], 'helpdesk');

            $fresh = $ticket->fresh();
            $this->assertNotNull($fresh->last_message_at);
            $this->assertNotNull($fresh->first_response_at);
        }
    }

    public function test_user_without_per_ticket_permission_gets_403_and_nothing_is_created(): void
    {
        // viewAny passes (helpdesk.tickets.view) but the update policy denies
        // every ticket: not assignee, no helpdesk.tickets.update permission.
        $user = $this->makeUser(['helpdesk.tickets.view']);
        $ticketA = $this->createTicket();
        $ticketB = $this->createTicket();

        $this->actingAs($user)
            ->postJson(route('manager.helpdesk.tickets.bulk-reply'), [
                'ticket_ids' => [$ticketA->id, $ticketB->id],
                'body' => 'No debería crearse',
            ])
            ->assertForbidden()
            ->assertJsonCount(2, 'skipped_ticket_ids');

        $this->assertDatabaseMissing('helpdesk_ticket_items', [
            'body' => 'No debería crearse',
        ], 'helpdesk');
    }

    public function test_unauthorized_tickets_are_skipped_and_reported(): void
    {
        // Assignee of ticket A only: policy update passes for A, denies B.
        $user = $this->makeUser(['helpdesk.tickets.view']);
        $ticketA = $this->createTicket(['assignee_id' => $user->id]);
        $ticketB = $this->createTicket();

        $response = $this->actingAs($user)
            ->postJson(route('manager.helpdesk.tickets.bulk-reply'), [
                'ticket_ids' => [$ticketA->id, $ticketB->id],
                'body' => 'Solo para mis tickets',
            ]);

        $response->assertOk()
            ->assertJsonPath('replied_ticket_ids.0', $ticketA->id)
            ->assertJsonPath('skipped_ticket_ids.0', $ticketB->id);

        $this->assertDatabaseHas('helpdesk_ticket_items', [
            'ticket_id' => $ticketA->id,
            'body' => 'Solo para mis tickets',
        ], 'helpdesk');

        $this->assertDatabaseMissing('helpdesk_ticket_items', [
            'ticket_id' => $ticketB->id,
            'body' => 'Solo para mis tickets',
        ], 'helpdesk');
    }

    public function test_bulk_reply_requires_authentication(): void
    {
        $ticket = $this->createTicket();

        $this->post(route('manager.helpdesk.tickets.bulk-reply'), [
            'ticket_ids' => [$ticket->id],
            'body' => 'Anon',
        ])->assertRedirect('/login');
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
            'subject' => 'Bulk reply test ticket',
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
