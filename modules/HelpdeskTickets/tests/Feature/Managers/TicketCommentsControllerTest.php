<?php

namespace Modules\HelpdeskTickets\Tests\Feature\Managers;

use App\Models\User;
use Illuminate\Mail\SendQueuedMailable;
use Illuminate\Support\Facades\Queue;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Database\Seeders\HelpdeskTicketsPermissionsSeeder;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketComment;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Tests\Concerns\SharesHelpdeskPdo;
use Tests\Concerns\SeedsHelpdeskRoles;
use Tests\TestCase;

class TicketCommentsControllerTest extends TestCase
{
    use SeedsHelpdeskRoles;

    // mariadb y helpdesk apuntan a la misma BD: PDO compartido evita
    // auto-interbloqueos de FK y garantiza rollback de AMBAS conexiones
    // (antes solo se transaccionaba mariadb y los tickets se filtraban).
    use SharesHelpdeskPdo;

    private User $agent;

    private Customer $customer;

    private TicketStatus $openStatus;

    protected function setUp(): void
    {
        parent::setUp();

        // La BD de test arranca sin roles; las rutas manager llevan role: middleware.
        $this->seedHelpdeskRoles();
        $this->seed(HelpdeskTicketsPermissionsSeeder::class);

        $this->agent = User::factory()->create();
        $this->agent->assignRole('super-settings');

        $this->agent->givePermissionTo(['helpdesk.tickets.view', 'helpdesk.tickets.update']);

        $this->openStatus = TicketStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );

        $this->customer = Customer::firstOrCreate(
            ['email' => 'comments-test-customer@example.com'],
            ['name' => 'Comments Test Customer']
        );
    }

    public function test_store_creates_external_comment_and_queues_customer_email(): void
    {
        Queue::fake();

        // Real customer with an email so the reply notification resolves a recipient.
        $ticket = $this->createTicket();

        $this->actingAs($this->agent)
            ->postJson(route('manager.helpdesk.tickets.comments.store', $ticket), [
                'ticket_id' => $ticket->id,
                'body' => 'This is an external reply to the customer.',
                'is_internal' => false,
            ])
            ->assertCreated();

        $this->assertDatabaseHas('helpdesk_ticket_comments', [
            'ticket_id' => $ticket->id,
            'is_internal' => false,
        ], 'helpdesk');

        // Mail was queued (TicketReplyMail uses Mail::queue)
        Queue::assertPushed(SendQueuedMailable::class);

        // Regresión: esta vía (comentario externo) enviaba el correo sin dejar
        // rastro en TicketMail — a diferencia de la vía TicketItem/MessageAdded.
        $this->assertDatabaseHas('helpdesk_ticket_mails', [
            'ticket_id' => $ticket->id,
            'user_id' => $this->agent->id,
            'direction' => 'outbound',
            'to' => $ticket->customer->email,
            'status' => 'sent',
        ], 'helpdesk');
    }

    public function test_internal_comment_does_not_queue_customer_email(): void
    {
        Queue::fake();

        // Cliente real del setUp — un id hardcodeado rompe el FK en una BD limpia.
        $ticket = $this->createTicket(['customer_id' => $this->customer->id]);

        $this->actingAs($this->agent)
            ->postJson(route('manager.helpdesk.tickets.comments.store', $ticket), [
                'ticket_id' => $ticket->id,
                'body' => 'This is an internal note only agents see.',
                'is_internal' => true,
            ])
            ->assertCreated();

        Queue::assertNotPushed(SendQueuedMailable::class);
    }

    public function test_show_comment_returns_404_when_comment_belongs_to_other_ticket(): void
    {
        $ticket = $this->createTicket();
        $otherTicket = $this->createTicket();

        $comment = TicketComment::create([
            'ticket_id' => $otherTicket->id,
            'user_id' => $this->agent->id,
            'body' => 'This comment belongs to another ticket.',
            'is_internal' => false,
        ]);

        $this->actingAs($this->agent)
            ->getJson(route('manager.helpdesk.tickets.comments.show', [$ticket, $comment]))
            ->assertNotFound();
    }

    public function test_update_comment_returns_404_when_comment_belongs_to_other_ticket(): void
    {
        $ticket = $this->createTicket();
        $otherTicket = $this->createTicket();

        $comment = TicketComment::create([
            'ticket_id' => $otherTicket->id,
            'user_id' => $this->agent->id,
            'body' => 'Comment on other ticket.',
            'is_internal' => false,
        ]);

        $this->actingAs($this->agent)
            ->putJson(route('manager.helpdesk.tickets.comments.update', [$ticket, $comment]), [
                'body' => 'Trying to edit from wrong ticket.',
            ])
            ->assertNotFound();
    }

    public function test_store_requires_authentication(): void
    {
        $ticket = $this->createTicket();

        $this->postJson(route('manager.helpdesk.tickets.comments.store', $ticket), [
            'ticket_id' => $ticket->id,
            'body' => 'Unauthenticated comment.',
        ])
            ->assertUnauthorized();
    }

    public function test_store_requires_update_permission(): void
    {
        $unauthorized = User::factory()->create();
        $ticket = $this->createTicket();

        $this->actingAs($unauthorized)
            ->postJson(route('manager.helpdesk.tickets.comments.store', $ticket), [
                'ticket_id' => $ticket->id,
                'body' => 'No permission comment.',
            ])
            ->assertForbidden();
    }

    public function test_show_returns_comment_when_it_belongs_to_ticket(): void
    {
        $ticket = $this->createTicket();

        $comment = TicketComment::create([
            'ticket_id' => $ticket->id,
            'user_id' => $this->agent->id,
            'body' => 'Correct comment for this ticket.',
            'is_internal' => false,
        ]);

        $this->actingAs($this->agent)
            ->getJson(route('manager.helpdesk.tickets.comments.show', [$ticket, $comment]))
            ->assertOk()
            ->assertJsonFragment(['id' => $comment->id]);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function createTicket(array $overrides = []): Ticket
    {
        return Ticket::create(array_merge([
            'subject' => 'Comment test ticket',
            'description' => 'Test description.',
            'customer_id' => $this->customer->id,
            'status_id' => $this->openStatus->id,
            'priority' => 'normal',
            'source' => 'web',
        ], $overrides));
    }
}
