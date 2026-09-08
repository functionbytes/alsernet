<?php

namespace Modules\HelpdeskTickets\Tests\Feature\Managers;

use App\Models\User;
use Illuminate\Support\Facades\Event;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Database\Seeders\HelpdeskTicketsPermissionsSeeder;
use Modules\HelpdeskTickets\Events\TicketAssigned;
use Modules\HelpdeskTickets\Events\TicketClosed;
use Modules\HelpdeskTickets\Events\TicketReopened;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketEmailBlacklist;
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

    /**
     * Bug real (ago-2026): el endpoint real de "Cerrar ticket" nunca disparaba
     * TicketClosed, así que la encuesta CSAT automática (UpdateTicketOnClose)
     * y las automatizaciones "al cerrar" no corrían nunca desde la UI.
     */
    public function test_closing_a_ticket_dispatches_ticket_closed_event(): void
    {
        Event::fake([TicketClosed::class]);

        $ticket = $this->createTicket();

        $this->actingAs($this->manager)
            ->post(route('manager.helpdesk.tickets.close', $ticket))
            ->assertRedirect();

        Event::assertDispatched(TicketClosed::class, fn (TicketClosed $event) => $event->ticket->is($ticket));
    }

    /**
     * Mismo bug que el de arriba pero en reopen(): sin TicketReopened,
     * SendCustomerReopenNotification nunca notificaba al cliente.
     */
    public function test_reopening_a_ticket_dispatches_ticket_reopened_event(): void
    {
        Event::fake([TicketReopened::class]);

        $ticket = $this->createTicket(['status_id' => $this->closedStatus->id, 'closed_at' => now()]);

        $this->actingAs($this->manager)
            ->post(route('manager.helpdesk.tickets.reopen', $ticket))
            ->assertRedirect();

        Event::assertDispatched(TicketReopened::class, fn (TicketReopened $event) => $event->ticket->is($ticket));
    }

    /**
     * Bug real: reasignar desde el formulario de edición de la ficha (a
     * diferencia de AssignmentService::assignTicket()) nunca disparaba
     * TicketAssigned, así que NotifyAgentOfAssignment/
     * RunAutomationsOnTicketAssigned no corrían al cambiar el agente aquí.
     */
    public function test_reassigning_a_ticket_via_update_dispatches_ticket_assigned_event(): void
    {
        Event::fake([TicketAssigned::class]);

        $agent = User::factory()->create();
        $ticket = $this->createTicket();

        $this->actingAs($this->manager)
            ->put(route('manager.helpdesk.tickets.update', $ticket), [
                'priority' => $ticket->priority,
                'status_id' => $ticket->status_id,
                'assignee_id' => $agent->id,
            ])
            ->assertRedirect();

        Event::assertDispatched(TicketAssigned::class, fn (TicketAssigned $event) => $event->ticket->is($ticket) && $event->agent->is($agent));
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

    public function test_show_redirects_to_index_with_ticket_preselected(): void
    {
        // La URL corta /tickets/{id} ya no renderiza una página aparte: redirige
        // al listado con el ticket preseleccionado, igual que el inbox de
        // conversaciones (ConversationsController::show).
        $ticket = $this->createTicket();

        $this->actingAs($this->manager)
            ->get(route('manager.helpdesk.tickets.show', $ticket))
            ->assertRedirect(route('manager.helpdesk.tickets.index', ['ticket' => $ticket->id]));
    }

    public function test_manager_can_view_ticket_full_detail_page(): void
    {
        $ticket = $this->createTicket();

        $this->actingAs($this->manager)
            ->get(route('manager.helpdesk.tickets.show-full', $ticket))
            ->assertOk();
    }

    /**
     * El composer de "correo suelto" que antes solo existía en la bandeja
     * global /tickets/emails (retirada) ahora vive aquí, apuntando al mismo
     * endpoint (manager.helpdesk.tickets.emails.store) — ver
     * ticket-detail.js#tkt-compose-mail-form.
     */
    public function test_ticket_detail_page_includes_the_email_composer_modal(): void
    {
        $ticket = $this->createTicket();

        $this->actingAs($this->manager)
            ->get(route('manager.helpdesk.tickets.show-full', $ticket))
            ->assertOk()
            ->assertSee('tkt-compose-mail-modal', false)
            ->assertSee('tkt-compose-template', false)
            ->assertSee(route('manager.helpdesk.tickets.emails.store'), false);
    }

    // ─── block sender quick action ───────────────────────────────────────────
    //
    // No hay caso "sin permiso" que probar aquí: esta página solo la abren
    // usuarios con rol super-admin|super-settings (gate del grupo de rutas en
    // HelpdeskTicketsServiceProvider), y HelpdeskTicketsPermissionsSeeder ya
    // otorga helpdesk.tickets.settings a esos dos roles completos — quien ve
    // el ticket siempre puede bloquear remitentes, por diseño.

    public function test_block_sender_button_shown_when_customer_not_blacklisted(): void
    {
        $ticket = $this->createTicket();

        $response = $this->actingAs($this->manager)
            ->get(route('manager.helpdesk.tickets.show-full', $ticket));

        $response->assertOk();
        $response->assertViewHas('blacklistMatch', null);
        $response->assertSee('Bloquear remitente');
    }

    public function test_block_sender_alert_shown_when_customer_already_blacklisted(): void
    {
        TicketEmailBlacklist::create([
            'type' => 'email',
            'value' => $this->customer->email,
        ]);
        $ticket = $this->createTicket();

        $response = $this->actingAs($this->manager)
            ->get(route('manager.helpdesk.tickets.show-full', $ticket));

        $response->assertOk();
        $response->assertSee('Remitente en lista negra');
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

        // El regex anterior era /data-tickets="(.*?)"\s+data-statuses/, que
        // daba por hecho que los dos atributos iban pegados. Al añadirse otros
        // data-* entre medias dejó de casar y $matches quedaba vacío: el test
        // moría con "Trying to access array offset on null" en vez de decir qué
        // fallaba. Basta con anclar en el atributo que se quiere leer.
        preg_match('/data-tickets="([^"]*)"/', $response->getContent(), $matches);
        $this->assertNotEmpty($matches, 'La vista debe exponer data-tickets con el payload del listado.');
        $tickets = json_decode(html_entity_decode($matches[1]), true);
        $payload = collect($tickets)->firstWhere('id', $ticket->id);

        $this->assertSame(1, $payload['unread_count']);
    }

    /**
     * TicketsCrudController::scopeToVisibleTickets() — 8-sep-2026: los
     * tickets sin equipo (group_id NULL, la mayoría de lo que entra sin
     * enrutar) son un bote compartido visible para cualquiera con permiso
     * base de ver tickets, no solo para helpdesk.tickets.manage. Sin esto la
     * pestaña "Sin asignar" quedaba prácticamente vacía para un agente base.
     */
    public function test_un_agente_base_ve_los_tickets_sin_equipo_en_el_listado(): void
    {
        $agente = User::factory()->create();
        // Las rutas manager exigen role:helpdesk-agent|... además del permiso
        // fino — sin el rol, el middleware corta antes de llegar a la Policy.
        $agente->assignRole('helpdesk-agent');
        $agente->givePermissionTo('helpdesk.tickets.view');

        $ticketSinEquipo = $this->createTicket(['subject' => 'Ticket sin equipo asignar', 'group_id' => null]);

        $response = $this->actingAs($agente)->get(route('manager.helpdesk.tickets.index'));
        $response->assertOk();

        preg_match('/data-tickets="([^"]*)"/', $response->getContent(), $matches);
        $tickets = json_decode(html_entity_decode($matches[1]), true);

        $this->assertNotNull(
            collect($tickets)->firstWhere('id', $ticketSinEquipo->id),
            'Un ticket sin equipo debe verse aunque el agente no sea el asignado ni pertenezca a ningún equipo.'
        );
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
