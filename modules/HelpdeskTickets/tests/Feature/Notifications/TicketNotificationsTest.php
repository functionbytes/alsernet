<?php

namespace Modules\HelpdeskTickets\Tests\Feature\Notifications;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Events\TicketAssigned;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketSlaBreach;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Notifications\TicketAssigned as TicketAssignedNotification;
use Modules\HelpdeskTickets\Notifications\TicketCreated as TicketCreatedNotification;
use Modules\HelpdeskTickets\Notifications\TicketMentionNotification;
use Modules\HelpdeskTickets\Notifications\TicketSlaBreached;
use Modules\HelpdeskTickets\Notifications\TicketSlaNearBreach;
use Modules\HelpdeskTickets\Notifications\TicketStatusChanged as TicketStatusChangedNotification;
use Tests\TestCase;

class TicketNotificationsTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    private Customer $customer;

    private TicketStatus $openStatus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->openStatus = TicketStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );

        $this->customer = Customer::firstOrCreate(
            ['email' => 'notifications-test-customer@example.com'],
            ['name' => 'Notifications Test Customer']
        );
    }

    // ─── TicketAssigned ───────────────────────────────────────────────────────

    public function test_ticket_assigned_notification_sent_to_assignee(): void
    {
        Notification::fake();

        $agent = User::factory()->create();
        $assigner = User::factory()->create();
        $ticket = $this->createTicket(['assignee_id' => $agent->id]);

        $agent->notify(new TicketAssignedNotification($ticket, $assigner));

        Notification::assertSentTo($agent, TicketAssignedNotification::class);
    }

    public function test_ticket_assigned_notification_not_sent_to_wrong_user(): void
    {
        Notification::fake();

        $agent = User::factory()->create();
        $bystander = User::factory()->create();
        $assigner = User::factory()->create();
        $ticket = $this->createTicket(['assignee_id' => $agent->id]);

        $agent->notify(new TicketAssignedNotification($ticket, $assigner));

        Notification::assertNotSentTo($bystander, TicketAssignedNotification::class);
    }

    // ─── TicketCreated ────────────────────────────────────────────────────────

    public function test_ticket_created_notification_sent_to_target_user(): void
    {
        Notification::fake();

        $manager = User::factory()->create();
        $ticket = $this->createTicket();

        $manager->notify(new TicketCreatedNotification($ticket));

        Notification::assertSentTo($manager, TicketCreatedNotification::class);
    }

    // ─── TicketSlaBreached ────────────────────────────────────────────────────

    public function test_ticket_sla_breached_notification_sent_to_assignee(): void
    {
        Notification::fake();

        $agent = User::factory()->create();
        $ticket = $this->createTicket(['assignee_id' => $agent->id]);

        $breach = TicketSlaBreach::create([
            'ticket_id' => $ticket->id,
            'breach_type' => 'first_response',
            'due_at' => now()->subHour(),
            'breached_at' => now(),
            'breach_duration_minutes' => 60,
            'resolved' => false,
        ]);

        $agent->notify(new TicketSlaBreached($ticket, $breach));

        Notification::assertSentTo($agent, TicketSlaBreached::class);
    }

    // ─── TicketSlaNearBreach ──────────────────────────────────────────────────

    public function test_ticket_sla_near_breach_notification_sent_to_assignee(): void
    {
        Notification::fake();

        $agent = User::factory()->create();
        $ticket = $this->createTicket(['assignee_id' => $agent->id]);

        $agent->notify(new TicketSlaNearBreach($ticket, 'first_response', 15));

        Notification::assertSentTo($agent, TicketSlaNearBreach::class);
    }

    // ─── TicketMentionNotification ────────────────────────────────────────────

    public function test_ticket_mention_notification_sent_to_mentioned_user(): void
    {
        Notification::fake();

        $mentionedUser = User::factory()->create();
        $mentionedBy = User::factory()->create();
        $ticket = $this->createTicket();

        $mentionedUser->notify(new TicketMentionNotification($ticket, $mentionedBy));

        Notification::assertSentTo($mentionedUser, TicketMentionNotification::class);
    }

    public function test_ticket_mention_notification_not_sent_to_mentioner(): void
    {
        Notification::fake();

        $mentionedUser = User::factory()->create();
        $mentionedBy = User::factory()->create();
        $ticket = $this->createTicket();

        $mentionedUser->notify(new TicketMentionNotification($ticket, $mentionedBy));

        Notification::assertNotSentTo($mentionedBy, TicketMentionNotification::class);
    }

    // ─── TicketStatusChanged ──────────────────────────────────────────────────

    public function test_ticket_status_changed_notification_sent_to_assignee(): void
    {
        Notification::fake();

        $agent = User::factory()->create();
        $ticket = $this->createTicket(['assignee_id' => $agent->id]);

        $agent->notify(new TicketStatusChangedNotification($ticket, 'Abierto', 'Cerrado'));

        Notification::assertSentTo($agent, TicketStatusChangedNotification::class);
    }

    // ─── Notification data shapes ─────────────────────────────────────────────

    public function test_ticket_assigned_notification_returns_expected_database_payload(): void
    {
        $assigner = User::factory()->create();
        $ticket = $this->createTicket();

        $notification = new TicketAssignedNotification($ticket, $assigner);
        $agent = User::factory()->create();
        $payload = $notification->toDatabase($agent);

        $this->assertArrayHasKey('ticket_id', $payload);
        $this->assertArrayHasKey('title', $payload);
        $this->assertArrayHasKey('action_url', $payload);
        $this->assertEquals($ticket->id, $payload['ticket_id']);
    }

    public function test_ticket_sla_near_breach_notification_includes_remaining_minutes(): void
    {
        $ticket = $this->createTicket();

        $notification = new TicketSlaNearBreach($ticket, 'resolution', 30);
        $agent = User::factory()->create();
        $payload = $notification->toDatabase($agent);

        $this->assertArrayHasKey('remaining_minutes', $payload);
        $this->assertEquals(30, $payload['remaining_minutes']);
        $this->assertEquals('resolution', $payload['breach_type']);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function createTicket(array $overrides = []): Ticket
    {
        return Ticket::create(array_merge([
            'subject' => 'Notification test ticket',
            'description' => 'Test description.',
            'customer_id' => $this->customer->id,
            'ticket_number' => 'TKT-'.now()->year.'-'.str_pad(rand(1000, 9999), 5, '0', STR_PAD_LEFT),
            'status_id' => $this->openStatus->id,
            'priority' => 'normal',
            'source' => 'web',
        ], $overrides));
    }
}
