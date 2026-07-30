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

class ManagersTicketsCrudTest extends TestCase
{
    use SeedsHelpdeskRoles;

    // mariadb y helpdesk apuntan a la misma BD: PDO compartido evita
    // auto-interbloqueos de FK y garantiza rollback de AMBAS conexiones
    // (antes solo se transaccionaba mariadb y los tickets se filtraban).
    use SharesHelpdeskPdo;

    private User $manager;

    private Customer $customer;

    private TicketStatus $openStatus;

    private TicketStatus $closedStatus;

    protected function setUp(): void
    {
        parent::setUp();

        // La BD de test arranca sin roles; las rutas manager llevan role: middleware.
        $this->seedHelpdeskRoles();
        $this->seed(HelpdeskTicketsPermissionsSeeder::class);

        $this->manager = User::factory()->create();
        $this->manager->assignRole('super-settings');

        $this->manager->givePermissionTo([
            'helpdesk.tickets.view',
            'helpdesk.tickets.create',
            'helpdesk.tickets.update',
            'helpdesk.tickets.delete',
            'helpdesk.tickets.close',
        ]);

        $this->openStatus = TicketStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );

        $this->closedStatus = TicketStatus::firstOrCreate(
            ['slug' => 'closed'],
            ['name' => 'Closed', 'color' => '#6c757d', 'is_open' => false, 'is_default' => false, 'order' => 2]
        );

        $this->customer = Customer::firstOrCreate(
            ['email' => 'manager-crud-customer@example.com'],
            ['name' => 'Manager CRUD Customer']
        );
    }

    public function test_manager_can_create_ticket_via_store(): void
    {
        $this->actingAs($this->manager)
            ->post(route('manager.helpdesk.tickets.store'), [
                'description' => 'Customer cannot log in to the system.',
                'priority' => 'high',
                'status_id' => $this->openStatus->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('helpdesk_tickets', [
            'description' => 'Customer cannot log in to the system.',
            'priority' => 'high',
        ], 'helpdesk');
    }

    public function test_manager_can_update_ticket(): void
    {
        $ticket = $this->createTicket();

        $this->actingAs($this->manager)
            ->put(route('manager.helpdesk.tickets.update', $ticket), [
                'priority' => 'urgent',
                'status_id' => $this->openStatus->id,
            ])
            ->assertRedirect();

        $this->assertEquals('urgent', $ticket->fresh()->priority);
    }

    public function test_manager_can_close_ticket(): void
    {
        $ticket = $this->createTicket();

        $this->actingAs($this->manager)
            ->post(route('manager.helpdesk.tickets.close', $ticket))
            ->assertRedirect();

        $this->assertNotNull($ticket->fresh()->closed_at);
    }

    public function test_close_requires_authentication(): void
    {
        $ticket = $this->createTicket();

        $this->post(route('manager.helpdesk.tickets.close', $ticket))
            ->assertRedirect('/login');
    }

    public function test_store_returns_403_without_create_permission(): void
    {
        $agent = User::factory()->create();

        $this->actingAs($agent)
            ->post(route('manager.helpdesk.tickets.store'), [
                'description' => 'No permission ticket.',
                'priority' => 'normal',
            ])
            ->assertForbidden();
    }

    public function test_store_validates_missing_description(): void
    {
        $this->actingAs($this->manager)
            ->post(route('manager.helpdesk.tickets.store'), [
                'priority' => 'normal',
            ])
            ->assertSessionHasErrors(['description']);
    }

    public function test_store_validates_invalid_priority(): void
    {
        $this->actingAs($this->manager)
            ->post(route('manager.helpdesk.tickets.store'), [
                'description' => 'Valid description.',
                'priority' => 'super-high',
            ])
            ->assertSessionHasErrors(['priority']);
    }

    public function test_user_without_view_permission_cannot_list_tickets(): void
    {
        $agent = User::factory()->create();

        $this->actingAs($agent)
            ->get(route('manager.helpdesk.tickets.index'))
            ->assertForbidden();
    }

    public function test_show_requires_view_permission(): void
    {
        $ticket = $this->createTicket();
        $agent = User::factory()->create();

        $this->actingAs($agent)
            ->get(route('manager.helpdesk.tickets.show', $ticket))
            ->assertForbidden();
    }

    public function test_manager_can_view_ticket_detail(): void
    {
        $ticket = $this->createTicket();

        $this->actingAs($this->manager)
            ->get(route('manager.helpdesk.tickets.show', $ticket))
            ->assertOk();
    }

    public function test_index_shows_correct_unread_count_per_ticket(): void
    {
        $ticket = $this->createTicket();

        $readItem = $ticket->items()->create([
            'type' => 'message',
            'author_id' => $this->customer->id,
            'body' => 'Read message',
        ]);

        $ticket->items()->create([
            'type' => 'message',
            'author_id' => $this->customer->id,
            'body' => 'Unread message',
        ]);

        $readItem->markAsRead($this->manager->id);

        $response = $this->actingAs($this->manager)
            ->get(route('manager.helpdesk.tickets.index'));

        $response->assertOk();

        preg_match('/data-tickets="(.*?)"\s+data-statuses/s', $response->getContent(), $matches);
        $tickets = json_decode(html_entity_decode($matches[1]), true);
        $payload = collect($tickets)->firstWhere('id', $ticket->id);

        $this->assertSame(1, $payload['unread_count']);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function createTicket(array $overrides = []): Ticket
    {
        return Ticket::create(array_merge([
            'subject' => 'Test ticket',
            'description' => 'Test description.',
            'customer_id' => $this->customer->id,
            'status_id' => $this->openStatus->id,
            'priority' => 'normal',
            'source' => 'web',
        ], $overrides));
    }
}
