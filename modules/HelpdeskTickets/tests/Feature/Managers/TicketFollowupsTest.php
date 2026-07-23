<?php

namespace Modules\HelpdeskTickets\Tests\Feature\Managers;

use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Database\Seeders\HelpdeskTicketsPermissionsSeeder;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Notifications\TicketFollowupDueNotification;
use Modules\HelpdeskTickets\Tests\Concerns\SharesHelpdeskPdo;
use Tests\Concerns\SeedsHelpdeskRoles;
use Tests\TestCase;

class TicketFollowupsTest extends TestCase
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
            ['email' => 'followups@example.com'],
            ['name' => 'Followups Customer']
        );
        $this->status = TicketStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );
    }

    public function test_agent_can_schedule_a_followup(): void
    {
        $ticket = $this->ticket();

        $this->actingAs($this->agent)
            ->postJson(route('manager.helpdesk.tickets.followups.store', $ticket), [
                'scheduled_at' => now()->addDay()->toDateTimeString(),
                'note' => 'Llamar al cliente',
            ])
            ->assertCreated()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('helpdesk_ticket_followups', [
            'ticket_id' => $ticket->id,
            'note' => 'Llamar al cliente',
            'is_sent' => false,
        ], 'helpdesk');
    }

    public function test_scheduled_at_must_be_in_the_future(): void
    {
        $ticket = $this->ticket();

        $this->actingAs($this->agent)
            ->postJson(route('manager.helpdesk.tickets.followups.store', $ticket), [
                'scheduled_at' => now()->subHour()->toDateTimeString(),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['scheduled_at']);
    }

    public function test_destroy_rejects_a_followup_from_another_ticket(): void
    {
        $ticket = $this->ticket();
        $otherTicket = $this->ticket();
        $followup = $otherTicket->followups()->create([
            'user_id' => $this->agent->id,
            'scheduled_at' => now()->addDay(),
            'is_sent' => false,
        ]);

        $this->actingAs($this->agent)
            ->deleteJson(route('manager.helpdesk.tickets.followups.destroy', [$ticket, $followup]))
            ->assertNotFound();
    }

    public function test_command_notifies_due_followups_and_marks_them_sent(): void
    {
        Notification::fake();

        $ticket = $this->ticket();
        $due = $ticket->followups()->create([
            'user_id' => $this->agent->id,
            'scheduled_at' => now()->subMinutes(5),
            'is_sent' => false,
        ]);
        $future = $ticket->followups()->create([
            'user_id' => $this->agent->id,
            'scheduled_at' => now()->addDay(),
            'is_sent' => false,
        ]);

        $this->artisan('ticket:send-followups')->assertSuccessful();

        Notification::assertSentTo($this->agent, TicketFollowupDueNotification::class);
        $this->assertTrue($due->fresh()->is_sent);
        $this->assertNotNull($due->fresh()->sent_at);
        $this->assertFalse($future->fresh()->is_sent);
    }

    private function ticket(array $overrides = []): Ticket
    {
        return Ticket::create(array_merge([
            'subject' => 'Followup ticket',
            'description' => 'x',
            'customer_id' => $this->customer->id,
            'status_id' => $this->status->id,
            'priority' => 'normal',
            'source' => 'web',
        ], $overrides));
    }
}
