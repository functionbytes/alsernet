<?php

namespace Modules\HelpdeskTickets\Tests\Feature\Managers;

use App\Models\User;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Database\Seeders\HelpdeskTicketsPermissionsSeeder;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Tests\Concerns\SharesHelpdeskPdo;
use Tests\Concerns\SeedsHelpdeskRoles;
use Tests\TestCase;

class ScheduledRepliesTest extends TestCase
{
    use SeedsHelpdeskRoles;
    use SharesHelpdeskPdo;

    private User $agent;

    private Customer $customer;

    private TicketStatus $status;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedHelpdeskRoles();
        $this->seed(HelpdeskTicketsPermissionsSeeder::class);

        $this->agent = User::factory()->create();
        $this->agent->assignRole('super-settings');
        $this->agent->givePermissionTo(['helpdesk.tickets.view', 'helpdesk.tickets.update']);

        $this->customer = Customer::firstOrCreate(
            ['email' => 'scheduled-replies@example.com'],
            ['name' => 'Scheduled Replies Customer']
        );
        $this->status = TicketStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );
    }

    public function test_agent_can_schedule_a_reply(): void
    {
        $ticket = $this->ticket();

        $this->actingAs($this->agent)
            ->postJson(route('manager.helpdesk.tickets.scheduled-replies.store', $ticket), [
                'body' => 'Le confirmo mañana',
                'deliver_at' => now()->addDay()->toDateTimeString(),
            ])
            ->assertCreated()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('helpdesk_ticket_scheduled_replies', [
            'ticket_id' => $ticket->id,
            'body' => 'Le confirmo mañana',
            'sent_at' => null,
        ], 'helpdesk');
    }

    public function test_deliver_at_must_be_in_the_future(): void
    {
        $ticket = $this->ticket();

        $this->actingAs($this->agent)
            ->postJson(route('manager.helpdesk.tickets.scheduled-replies.store', $ticket), [
                'body' => 'x',
                'deliver_at' => now()->subHour()->toDateTimeString(),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['deliver_at']);
    }

    public function test_index_lists_only_pending_replies(): void
    {
        $ticket = $this->ticket();
        $pending = $ticket->scheduledReplies()->create([
            'user_id' => $this->agent->id,
            'body' => 'Pendiente',
            'deliver_at' => now()->addDay(),
        ]);
        $ticket->scheduledReplies()->create([
            'user_id' => $this->agent->id,
            'body' => 'Ya enviada',
            'deliver_at' => now()->subDay(),
            'sent_at' => now()->subDay(),
        ]);

        $this->actingAs($this->agent)
            ->getJson(route('manager.helpdesk.tickets.scheduled-replies.index', $ticket))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $pending->id);
    }

    public function test_destroy_cancels_a_pending_reply(): void
    {
        $ticket = $this->ticket();
        $reply = $ticket->scheduledReplies()->create([
            'user_id' => $this->agent->id,
            'body' => 'Cancelar',
            'deliver_at' => now()->addDay(),
        ]);

        $this->actingAs($this->agent)
            ->deleteJson(route('manager.helpdesk.tickets.scheduled-replies.destroy', [$ticket, $reply]))
            ->assertOk();

        $this->assertDatabaseMissing('helpdesk_ticket_scheduled_replies', ['id' => $reply->id], 'helpdesk');
    }

    public function test_destroy_rejects_an_already_sent_reply(): void
    {
        $ticket = $this->ticket();
        $reply = $ticket->scheduledReplies()->create([
            'user_id' => $this->agent->id,
            'body' => 'Enviada',
            'deliver_at' => now()->subDay(),
            'sent_at' => now()->subHour(),
        ]);

        $this->actingAs($this->agent)
            ->deleteJson(route('manager.helpdesk.tickets.scheduled-replies.destroy', [$ticket, $reply]))
            ->assertStatus(409);
    }

    public function test_command_delivers_due_replies_and_marks_them_sent(): void
    {
        $ticket = $this->ticket();
        $due = $ticket->scheduledReplies()->create([
            'user_id' => $this->agent->id,
            'body' => 'Respuesta vencida',
            'deliver_at' => now()->subMinutes(5),
        ]);
        $future = $ticket->scheduledReplies()->create([
            'user_id' => $this->agent->id,
            'body' => 'Respuesta futura',
            'deliver_at' => now()->addDay(),
        ]);

        $this->artisan('ticket:send-scheduled-replies')->assertSuccessful();

        $this->assertNotNull($due->fresh()->sent_at);
        $this->assertNull($future->fresh()->sent_at);

        // La respuesta vencida se materializó como mensaje del ticket.
        $this->assertDatabaseHas('helpdesk_ticket_items', [
            'ticket_id' => $ticket->id,
            'body' => 'Respuesta vencida',
            'type' => 'message',
        ], 'helpdesk');
    }

    private function ticket(array $overrides = []): Ticket
    {
        return Ticket::create(array_merge([
            'subject' => 'Scheduled reply ticket',
            'description' => 'x',
            'customer_id' => $this->customer->id,
            'status_id' => $this->status->id,
            'priority' => 'normal',
            'source' => 'web',
        ], $overrides));
    }
}
