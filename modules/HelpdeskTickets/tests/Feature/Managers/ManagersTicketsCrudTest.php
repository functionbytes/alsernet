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
use Modules\HelpdeskTickets\Models\TicketCategory;
use Modules\HelpdeskTickets\Models\TicketDraft;
use Modules\HelpdeskTickets\Models\TicketGroup;
use Modules\HelpdeskTickets\Models\TicketRead;
use Modules\HelpdeskTickets\Models\TicketReview;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Models\TicketTask;
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

        // Destino explícito, no solo assertRedirect() genérico: tras
        // eliminar la ficha completa (show-full, 8-sep-2026) este redirect
        // pasó a apuntar a 'show' (listado con panel superpuesto) — sin este
        // assert una regresión a una ruta borrada seguiría en verde.
        $this->actingAs($this->manager)
            ->put(route('manager.helpdesk.tickets.update', $ticket), [
                'priority' => 'urgent',
                'status_id' => $this->openStatus->id,
            ])
            ->assertRedirect(route('manager.helpdesk.tickets.show', $ticket));

        $this->assertEquals('urgent', $ticket->fresh()->priority);
    }

    public function test_json_update_rejects_a_stale_client_version(): void
    {
        $ticket = $this->createTicket();

        $this->actingAs($this->manager)
            ->putJson(route('manager.helpdesk.tickets.update', $ticket), [
                'priority' => 'urgent',
                'client_updated_at' => now()->subDay()->toIso8601String(),
            ])
            ->assertStatus(409)
            ->assertJsonPath('code', 'stale_ticket');

        $this->assertEquals('normal', $ticket->fresh()->priority);
    }

    public function test_detail_search_filters_the_thread_on_the_server(): void
    {
        $ticket = $this->createTicket();
        $ticket->items()->create([
            'author_id' => $this->customer->id,
            'type' => 'message',
            'body' => 'Necesito ayuda con la factura de marzo.',
            'is_internal' => false,
        ]);
        $ticket->items()->create([
            'user_id' => $this->manager->id,
            'type' => 'message',
            'body' => 'Revisaré el acceso a la cuenta.',
            'is_internal' => false,
        ]);

        $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.tickets.data', [$ticket, 'thread_search' => 'factura']))
            ->assertOk()
            ->assertJsonPath('thread_search', 'factura')
            ->assertJsonCount(1, 'thread')
            ->assertJsonPath('thread.0.body', 'Necesito ayuda con la factura de marzo.');

        $this->assertSame(
            2,
            TicketRead::where('user_id', $this->manager->id)
                ->whereIn('ticket_item_id', $ticket->items()->pluck('id'))
                ->count(),
            'Buscar una coincidencia no debe dejar el resto del hilo como no leído.'
        );
    }

    public function test_prefetch_of_detail_does_not_mark_the_thread_as_read(): void
    {
        $ticket = $this->createTicket();
        $ticket->items()->create([
            'author_id' => $this->customer->id,
            'type' => 'message',
            'body' => 'Sigo sin recibir el pedido.',
            'is_internal' => false,
        ]);
        $readCount = fn () => TicketRead::where('user_id', $this->manager->id)
            ->whereIn('ticket_item_id', $ticket->items()->pluck('id'))
            ->count();

        $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.tickets.data', [$ticket, 'prefetch' => 1]))
            ->assertOk()
            ->assertJsonCount(1, 'thread');
        $this->assertSame(0, $readCount(), 'Pasar el ratón por la fila no es abrir el ticket.');

        $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.tickets.data', $ticket))
            ->assertOk();
        $this->assertSame(1, $readCount());
    }

    public function test_repeated_message_with_same_idempotency_key_is_not_duplicated(): void
    {
        $ticket = $this->createTicket();
        $headers = ['X-Idempotency-Key' => 'manager-test-idempotency-9140'];
        $payload = ['body' => 'Respuesta procesada una sola vez.', 'is_internal' => false];

        $this->actingAs($this->manager)
            ->withHeaders($headers)
            ->postJson(route('manager.helpdesk.tickets.messages.store', $ticket), $payload)
            ->assertOk()
            ->assertJsonPath('idempotent_replay', false);

        $this->actingAs($this->manager)
            ->withHeaders($headers)
            ->postJson(route('manager.helpdesk.tickets.messages.store', $ticket), $payload)
            ->assertOk()
            ->assertJsonPath('idempotent_replay', true);

        $this->assertSame(1, $ticket->items()->where('body', $payload['body'])->count());
    }

    public function test_detail_thread_supports_incremental_pages(): void
    {
        $ticket = $this->createTicket();

        for ($i = 1; $i <= 35; $i++) {
            $ticket->items()->create([
                'author_id' => $this->customer->id,
                'type' => 'message',
                'body' => 'Mensaje histórico '.$i,
                'is_internal' => false,
            ]);
        }

        $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.tickets.data', [$ticket, 'thread_page' => 2, 'thread_per_page' => 30]))
            ->assertOk()
            ->assertJsonPath('thread_page', 2)
            ->assertJsonPath('thread_per_page', 30)
            ->assertJsonPath('thread_total', 35)
            ->assertJsonPath('thread_has_more', false)
            ->assertJsonCount(5, 'thread');
    }

    public function test_detail_channel_filter_accepts_formulario_alias_for_legacy_form_tickets(): void
    {
        $ticket = $this->createTicket(['source' => 'form']);
        $ticket->items()->create([
            'author_id' => $this->customer->id,
            'type' => 'message',
            'body' => 'Solicitud enviada desde el formulario.',
            'is_internal' => false,
        ]);

        $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.tickets.data', [$ticket, 'thread_channel' => 'formulario']))
            ->assertOk()
            ->assertJsonPath('thread_filters.channel', 'formulario')
            ->assertJsonCount(1, 'thread');
    }

    public function test_manager_can_close_ticket(): void
    {
        $ticket = $this->createTicket();

        $this->actingAs($this->manager)
            ->post(route('manager.helpdesk.tickets.close', $ticket))
            ->assertRedirect();

        $this->assertNotNull($ticket->fresh()->closed_at);
    }

    public function test_json_close_rejects_a_stale_client_version(): void
    {
        $ticket = $this->createTicket();

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.close', $ticket), [
                'client_updated_at' => now()->subDay()->toIso8601String(),
            ])
            ->assertStatus(409)
            ->assertJsonPath('code', 'stale_ticket');

        $this->assertNull($ticket->fresh()->closed_at);
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

        // Desde el fix de asignación (28-sep-2026), assignee_id exige un rol
        // de agente del helpdesk — ver AssignmentService::isAssignableAgent().
        $agent = User::factory()->create();
        $agent->assignRole('helpdesk-agent');
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

    /**
     * Bug real (28-sep-2026): UpdateTicketRequest::assignee_id aceptaba
     * CUALQUIER users.id, sin exigir ningún rol de agente del helpdesk —
     * ver AssignmentService::isAssignableAgent().
     */
    public function test_reassigning_a_ticket_to_a_non_agent_is_rejected(): void
    {
        $notAnAgent = User::factory()->create();
        $ticket = $this->createTicket();

        $this->actingAs($this->manager)
            ->put(route('manager.helpdesk.tickets.update', $ticket), [
                'priority' => $ticket->priority,
                'status_id' => $ticket->status_id,
                'assignee_id' => $notAnAgent->id,
            ])
            ->assertSessionHasErrors(['assignee_id']);

        $this->assertNull($ticket->fresh()->assignee_id);
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

    // La ficha completa (show-full: página aparte con composer de correo
    // suelto, botón "Bloquear remitente", etc.) se eliminó el 8-sep-2026 —
    // ver commit correspondiente. Sus 4 tests (renderizado de la página,
    // composer de email, botón/aviso de lista negra) se retiraron con ella:
    // el listado con el panel superpuesto cubre las mismas acciones
    // (modal "Pasar a lista negra" en tickets-app/core.js) sin una vista
    // dedicada que probar aquí.

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

    /**
     * Ticket::scopeSearch() encadena orWhere sin agrupar; es seguro solo
     * porque Eloquent agrupa las condiciones de un scope llamado sobre una
     * consulta con wheres previos. Este test fija ese comportamiento.
     */
    public function test_buscar_en_el_listado_no_salta_la_visibilidad_por_equipo(): void
    {
        $agente = User::factory()->create();
        $agente->assignRole('helpdesk-agent');
        $agente->givePermissionTo('helpdesk.tickets.view');

        $equipoAjeno = TicketGroup::create(['name' => 'Equipo ajeno '.uniqid()]);
        $marca = 'zqx-busqueda-'.uniqid();

        $ajeno = $this->createTicket(['subject' => "Ajeno {$marca}", 'group_id' => $equipoAjeno->id]);
        $propio = $this->createTicket(['subject' => "Propio {$marca}", 'group_id' => null]);

        $response = $this->actingAs($agente)->get(route('manager.helpdesk.tickets.index', ['search' => $marca]));
        $response->assertOk();

        preg_match('/data-tickets="([^"]*)"/', $response->getContent(), $matches);
        $ids = collect(json_decode(html_entity_decode($matches[1]), true))->pluck('id');

        $this->assertContains($propio->id, $ids);
        $this->assertNotContains($ajeno->id, $ids, 'La búsqueda no debe mostrar tickets de un equipo al que el agente no pertenece.');
    }

    public function test_los_buscadores_de_tickets_acotan_por_equipo(): void
    {
        $agente = User::factory()->create();
        $agente->assignRole('helpdesk-agent');
        $agente->givePermissionTo(['helpdesk.tickets.view', 'helpdesk.search.use']);

        $equipoAjeno = TicketGroup::create(['name' => 'Equipo ajeno '.uniqid()]);
        $marca = 'zqx-avanzada-'.uniqid();
        $ajeno = $this->createTicket(['subject' => "Ajeno {$marca}", 'group_id' => $equipoAjeno->id]);

        $this->actingAs($agente)
            ->get(route('manager.helpdesk.tickets.search', ['q' => $marca]))
            ->assertOk()
            ->assertDontSee($ajeno->ticket_number);

        $this->actingAs($agente)
            ->get(route('manager.helpdesk.search', ['q' => $marca]))
            ->assertOk()
            ->assertDontSee($ajeno->ticket_number);

        $this->actingAs($agente)
            ->getJson(route('manager.helpdesk.search.global', ['q' => $marca]))
            ->assertOk()
            ->assertJsonMissing(['id' => $ajeno->id]);
    }

    public function test_el_borrador_se_guarda_en_servidor_y_los_demas_solo_ven_que_existe(): void
    {
        $ticket = $this->createTicket();
        $otro = User::factory()->create();
        $otro->assignRole('super-settings');

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.draft.update', $ticket), ['body' => 'Texto secreto a medias', 'mode' => 'reply'])
            ->assertOk()->assertJson(['saved' => true]);

        $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.tickets.data', $ticket))
            ->assertOk()
            ->assertJsonPath('my_draft.body', 'Texto secreto a medias');

        $response = $this->actingAs($otro)->getJson(route('manager.helpdesk.tickets.data', $ticket))->assertOk();
        $this->assertNull($response->json('my_draft'));
        $this->assertCount(1, $response->json('others_drafting'));
        $this->assertStringNotContainsString('Texto secreto', $response->getContent());
    }

    public function test_enviar_la_respuesta_borra_el_borrador_del_servidor(): void
    {
        $ticket = $this->createTicket();

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.draft.update', $ticket), ['body' => 'Borrador', 'mode' => 'note']);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.messages.store', $ticket), ['body' => 'Nota final', 'is_internal' => true])
            ->assertOk();

        $this->assertSame(0, TicketDraft::query()->where('ticket_id', $ticket->id)->count());
    }

    public function test_checklist_de_tareas_se_crea_marca_y_borra(): void
    {
        $ticket = $this->createTicket();

        $taskId = $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.tasks.store', $ticket), ['title' => 'Pedir foto del precinto'])
            ->assertCreated()
            ->json('task.id');

        $this->actingAs($this->manager)
            ->patchJson(route('manager.helpdesk.tickets.tasks.update', [$ticket, $taskId]), ['is_done' => true])
            ->assertOk()
            ->assertJsonPath('task.is_done', true);

        $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.tickets.data', $ticket))
            ->assertJsonPath('work.tasks.0.title', 'Pedir foto del precinto');

        $this->actingAs($this->manager)
            ->deleteJson(route('manager.helpdesk.tickets.tasks.destroy', [$ticket, $taskId]))
            ->assertOk();
    }

    public function test_una_tarea_de_otro_ticket_no_se_puede_tocar(): void
    {
        $ticket = $this->createTicket();
        $otro = $this->createTicket();
        $task = TicketTask::create(['ticket_id' => $otro->id, 'title' => 'Ajena']);

        $this->actingAs($this->manager)
            ->patchJson(route('manager.helpdesk.tickets.tasks.update', [$ticket, $task->id]), ['is_done' => true])
            ->assertNotFound();
    }

    public function test_un_subticket_abierto_bloquea_el_cierre_del_padre(): void
    {
        $padre = $this->createTicket();

        $hijoId = $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.subtickets.store', $padre), ['subject' => 'Recoger el paquete'])
            ->assertCreated()
            ->json('ticket_id');

        $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.tickets.data', $padre))
            ->assertJsonPath('work.subtickets.0.id', $hijoId);

        $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.tickets.data', $hijoId))
            ->assertJsonPath('work.parent.id', $padre->id);

        $this->assertTrue($padre->fresh()->openBlockers()->pluck('id')->contains($hijoId));
    }

    public function test_el_agente_edita_los_campos_de_la_categoria_con_validacion(): void
    {
        $categoria = TicketCategory::create(['name' => 'Garantías '.uniqid(), 'slug' => 'garantias-'.uniqid(), 'active' => true]);
        $categoria->fields()->create(['type' => 'text', 'key' => 'pedido', 'label' => 'Nº de pedido', 'is_required' => true, 'is_visible' => true, 'width' => 'full', 'sort_order' => 1]);
        $categoria->fields()->create(['type' => 'select', 'key' => 'estado_caja', 'label' => 'Estado de la caja', 'is_required' => false, 'is_visible' => true, 'width' => 'full', 'sort_order' => 2, 'options' => [['value' => 'ok', 'label' => 'Bien'], ['value' => 'rota', 'label' => 'Rota']]]);
        $ticket = $this->createTicket(['category_id' => $categoria->id]);

        $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.tickets.data', $ticket))
            ->assertJsonPath('work.category_fields.0.key', 'pedido');

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.custom-fields.update', $ticket), ['fields' => ['pedido' => '', 'estado_caja' => 'inventado']])
            ->assertUnprocessable();

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.custom-fields.update', $ticket), ['fields' => ['pedido' => '45012', 'estado_caja' => 'rota']])
            ->assertOk()
            ->assertJsonPath('values.pedido', '45012');

        $this->assertSame('rota', $ticket->fresh()->custom_fields['estado_caja']);
    }

    public function test_la_revision_de_calidad_se_ve_y_se_puede_disputar(): void
    {
        $ticket = $this->createTicket();
        TicketReview::create([
            'ticket_id' => $ticket->id, 'score' => 42, 'summary' => 'Respuesta incompleta',
            'issues' => ['No se ofreció seguimiento'], 'dimensions' => [], 'disputed' => false,
        ]);

        $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.tickets.data', $ticket))
            ->assertJsonPath('work.quality_review.score', 42);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.ai.dispute-review', $ticket), ['note' => 'El cliente ya tenía la información'])
            ->assertOk();

        $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.tickets.data', $ticket))
            ->assertJsonPath('work.quality_review.disputed', true);
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
