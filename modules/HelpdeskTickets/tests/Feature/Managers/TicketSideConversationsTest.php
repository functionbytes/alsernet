<?php

namespace Modules\HelpdeskTickets\Tests\Feature\Managers;

use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Modules\HelpdeskTickets\Database\Seeders\HelpdeskTicketsPermissionsSeeder;
use Modules\HelpdeskTickets\Mail\TicketSideConversationMail;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Notifications\TicketSideConversationMessageNotification;
use Modules\HelpdeskTickets\Tests\Concerns\SharesHelpdeskPdo;
use Tests\Concerns\SeedsHelpdeskRoles;
use Tests\TestCase;

class TicketSideConversationsTest extends TestCase
{
    use SeedsHelpdeskRoles;
    use SharesHelpdeskPdo;

    private User $agent;

    private TicketStatus $status;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedHelpdeskRoles();
        $this->seed(HelpdeskTicketsPermissionsSeeder::class);

        $this->agent = User::factory()->create(['firstname' => 'Agente', 'lastname' => 'Uno']);
        $this->agent->assignRole('super-settings');
        $this->agent->givePermissionTo(['helpdesk.tickets.view', 'helpdesk.tickets.update']);

        $this->status = TicketStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );
    }

    public function test_team_side_conversation_notifies_the_participant(): void
    {
        Notification::fake();
        $ticket = $this->ticket();
        $colleague = User::factory()->create(['firstname' => 'Compa', 'lastname' => 'Dos']);

        $this->actingAs($this->agent)
            ->postJson(route('manager.helpdesk.tickets.side-conversations.store', $ticket), [
                'subject' => 'Consulta a logística',
                'participant_type' => 'team',
                'participant_user_id' => $colleague->id,
                'body' => '¿Tenemos stock de este artículo?',
            ])
            ->assertCreated()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('helpdesk_ticket_side_conversations', [
            'ticket_id' => $ticket->id,
            'subject' => 'Consulta a logística',
            'participant_user_id' => $colleague->id,
            'status' => 'open',
        ], 'helpdesk');

        Notification::assertSentTo($colleague, TicketSideConversationMessageNotification::class);
    }

    public function test_external_side_conversation_sends_an_email(): void
    {
        Mail::fake();
        $ticket = $this->ticket();

        $this->actingAs($this->agent)
            ->postJson(route('manager.helpdesk.tickets.side-conversations.store', $ticket), [
                'subject' => 'Consulta al proveedor',
                'participant_type' => 'external_email',
                'participant_email' => 'proveedor@example.com',
                'body' => 'Necesito plazo de entrega.',
            ])
            ->assertCreated();

        Mail::assertQueued(TicketSideConversationMail::class);
    }

    public function test_team_type_requires_a_colleague(): void
    {
        $ticket = $this->ticket();

        $this->actingAs($this->agent)
            ->postJson(route('manager.helpdesk.tickets.side-conversations.store', $ticket), [
                'subject' => 'Sin participante',
                'participant_type' => 'team',
                'body' => 'Hola',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['participant_user_id']);
    }

    public function test_adding_a_message_to_a_closed_side_conversation_is_rejected(): void
    {
        Notification::fake();
        $ticket = $this->ticket();
        $side = $ticket->sideConversations()->create([
            'subject' => 'Cerrada',
            'participant_type' => 'team',
            'participant_user_id' => $this->agent->id,
            'status' => 'closed',
            'created_by' => $this->agent->id,
        ]);

        $this->actingAs($this->agent)
            ->postJson(route('manager.helpdesk.tickets.side-conversations.messages.store', [$ticket, $side]), [
                'body' => 'Un mensaje más',
            ])
            ->assertStatus(409);
    }

    public function test_close_marks_the_side_conversation_closed(): void
    {
        $ticket = $this->ticket();
        $side = $ticket->sideConversations()->create([
            'subject' => 'Abierta',
            'participant_type' => 'team',
            'participant_user_id' => $this->agent->id,
            'status' => 'open',
            'created_by' => $this->agent->id,
        ]);

        $this->actingAs($this->agent)
            ->postJson(route('manager.helpdesk.tickets.side-conversations.close', [$ticket, $side]))
            ->assertOk();

        $this->assertSame('closed', $side->fresh()->status);
    }

    private function ticket(): Ticket
    {
        return Ticket::create([
            'subject' => 'Side conversation ticket',
            'description' => 'x',
            'status_id' => $this->status->id,
            'priority' => 'normal',
            'source' => 'web',
        ]);
    }
}
