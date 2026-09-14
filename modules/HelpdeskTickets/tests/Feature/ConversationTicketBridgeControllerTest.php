<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Modules\Helpdesk\Contracts\TicketServiceContract;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Events\TicketCreated;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketGroup;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Endpoints puente del modal "Escalar a ticket" de la bandeja.
 *
 * El servicio tenía pruebas (HelpdeskTicketBridgeServiceTest) pero los dos
 * endpoints HTTP no tenían ninguna, y ahí estaban los fallos reales: la
 * prioridad 'medium' que el modal enviaba y que no existe en el módulo, el
 * gate de rol que devolvía 403 a cualquier agente, las casillas del formulario
 * que no viajaban en la petición, y el detalle de ticket que no comprobaba a
 * qué conversación pertenecía.
 */
class ConversationTicketBridgeControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private User $gestor;

    private User $agente;

    private User $agenteSinPermiso;

    private Conversation $conversacion;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'helpdesk.tickets.create', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'helpdesk.conversations.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'helpdesk.manage', 'guard_name' => 'web']);

        // super-settings tiene bypass global (Gate::before en Auth), así que
        // sirve de "camino feliz" sin tocar permisos de roles reales.
        $this->gestor = User::factory()->create();
        $this->gestor->assignRole(Role::firstOrCreate(['name' => 'super-settings', 'guard_name' => 'web']));

        // Los permisos se dan al USUARIO, no al rol helpdesk-agent: tocar el
        // rol cambiaría lo que pueden hacer los agentes reales de la BD.
        $rolAgente = Role::firstOrCreate(['name' => 'helpdesk-agent', 'guard_name' => 'web']);

        $this->agente = User::factory()->create();
        $this->agente->assignRole($rolAgente);
        $this->agente->givePermissionTo('helpdesk.tickets.create', 'helpdesk.conversations.view', 'helpdesk.manage');

        $this->agenteSinPermiso = User::factory()->create();
        $this->agenteSinPermiso->assignRole($rolAgente);
        $this->agenteSinPermiso->givePermissionTo('helpdesk.conversations.view', 'helpdesk.manage');

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->conversacion = Conversation::factory()->create();
    }

    private function url(?Conversation $conversation = null): string
    {
        return route('manager.helpdesk.conversations.ticket', $conversation ?? $this->conversacion);
    }

    // ── Prioridad ─────────────────────────────────────────────

    public function test_guarda_la_prioridad_por_defecto_del_modal(): void
    {
        Event::fake([TicketCreated::class]);

        $id = $this->actingAs($this->gestor)
            ->postJson($this->url(), ['subject' => 'Escalado normal', 'priority' => 'normal'])
            ->assertOk()
            ->json('ticket.id');

        $this->assertSame('normal', Ticket::find($id)->priority);
    }

    public function test_rechaza_una_prioridad_que_el_modulo_no_conoce(): void
    {
        // 'medium' era EXACTAMENTE lo que enviaba el botón "Normal" del modal:
        // se guardaba tal cual porque el endpoint no validaba nada, y dejaba el
        // ticket fuera del orden por prioridad y sin color en el panel.
        $this->actingAs($this->gestor)
            ->postJson($this->url(), ['subject' => 'Prioridad inventada', 'priority' => 'medium'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('priority');

        $this->assertSame(0, Ticket::where('conversation_id', $this->conversacion->id)->count());
    }

    public function test_ninguna_prioridad_guardada_queda_fuera_del_orden_por_prioridad(): void
    {
        Event::fake([TicketCreated::class]);

        foreach (['low', 'normal', 'high', 'urgent'] as $prioridad) {
            $id = $this->actingAs($this->gestor)
                ->postJson($this->url(), ['subject' => "Escalado {$prioridad}", 'priority' => $prioridad])
                ->assertOk()
                ->json('ticket.id');

            // FIELD() devuelve 0 para lo que no esté en la lista: ese 0 es lo
            // que colaba los tickets con 'medium' por delante de los urgentes.
            $posicion = Ticket::where('id', $id)
                ->selectRaw("FIELD(priority, 'urgent', 'high', 'normal', 'low') as pos")
                ->value('pos');

            $this->assertGreaterThan(0, (int) $posicion, "la prioridad '{$prioridad}' no ordena");
        }
    }

    // ── Validación ────────────────────────────────────────────

    public function test_el_asunto_es_obligatorio(): void
    {
        $this->actingAs($this->gestor)
            ->postJson($this->url(), ['description' => 'sin asunto'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('subject');
    }

    public function test_el_asunto_no_puede_desbordar_la_columna(): void
    {
        $this->actingAs($this->gestor)
            ->postJson($this->url(), ['subject' => str_repeat('a', 192)])
            ->assertStatus(422)
            ->assertJsonValidationErrors('subject');
    }

    public function test_no_acepta_una_categoria_inexistente(): void
    {
        $this->actingAs($this->gestor)
            ->postJson($this->url(), ['subject' => 'Con categoría fantasma', 'category_id' => 99999999])
            ->assertStatus(422)
            ->assertJsonValidationErrors('category_id');
    }

    // ── Permisos ──────────────────────────────────────────────

    public function test_un_agente_de_la_bandeja_puede_escalar(): void
    {
        Event::fake([TicketCreated::class]);

        // Antes esto era un 403: las rutas puente colgaban del grupo
        // role:super-admin|super-settings mientras la bandeja se sirve con
        // ['web','auth'], así que el agente veía el botón y no podía usarlo.
        $this->actingAs($this->agente)
            ->postJson($this->url(), ['subject' => 'Escalado por un agente'])
            ->assertOk();
    }

    public function test_sin_el_permiso_de_crear_tickets_no_se_escala(): void
    {
        $this->actingAs($this->agenteSinPermiso)
            ->postJson($this->url(), ['subject' => 'Escalado sin permiso'])
            ->assertForbidden();

        $this->assertSame(0, Ticket::where('conversation_id', $this->conversacion->id)->count());
    }

    // ── Casillas del formulario ───────────────────────────────

    public function test_adjunta_la_transcripcion_cuando_se_marca_la_casilla(): void
    {
        Event::fake([TicketCreated::class]);

        ConversationItem::create([
            'conversation_id' => $this->conversacion->id,
            'type' => 'message',
            'body' => 'No puedo entrar en mi cuenta',
            'is_internal' => false,
        ]);

        $id = $this->actingAs($this->gestor)
            ->postJson($this->url(), ['subject' => 'Con transcripción', 'attach_transcript' => 1])
            ->assertOk()
            ->json('ticket.id');

        $notas = Ticket::find($id)->items()->where('is_internal', true)->pluck('body');

        $this->assertTrue(
            $notas->contains(fn ($b) => str_contains($b, 'Transcripción de la conversación')
                && str_contains($b, 'No puedo entrar en mi cuenta')),
            'la transcripción debe quedar como nota interna del ticket'
        );
    }

    public function test_sin_marcar_la_casilla_no_adjunta_transcripcion(): void
    {
        Event::fake([TicketCreated::class]);

        ConversationItem::create([
            'conversation_id' => $this->conversacion->id,
            'type' => 'message',
            'body' => 'Mensaje que no debe copiarse',
            'is_internal' => false,
        ]);

        $id = $this->actingAs($this->gestor)
            ->postJson($this->url(), ['subject' => 'Sin transcripción', 'attach_transcript' => 0])
            ->assertOk()
            ->json('ticket.id');

        $this->assertFalse(
            Ticket::find($id)->items()->pluck('body')
                ->contains(fn ($b) => str_contains($b, 'Transcripción de la conversación'))
        );
    }

    public function test_la_casilla_de_notificar_viaja_en_el_evento(): void
    {
        Event::fake([TicketCreated::class]);

        $this->actingAs($this->gestor)
            ->postJson($this->url(), ['subject' => 'Sin avisar al cliente', 'notify_customer' => 0])
            ->assertOk();

        Event::assertDispatched(
            TicketCreated::class,
            fn (TicketCreated $e) => $e->notifyCustomer === false
        );
    }

    public function test_por_defecto_el_cliente_si_recibe_la_confirmacion(): void
    {
        Event::fake([TicketCreated::class]);

        $this->actingAs($this->gestor)
            ->postJson($this->url(), ['subject' => 'Con aviso al cliente', 'notify_customer' => 1])
            ->assertOk();

        Event::assertDispatched(
            TicketCreated::class,
            fn (TicketCreated $e) => $e->notifyCustomer === true
        );
    }

    // ── El evento que enciende el resto del módulo ────────────

    public function test_escalar_dispara_ticket_created(): void
    {
        Event::fake([TicketCreated::class]);

        $this->actingAs($this->gestor)
            ->postJson($this->url(), ['subject' => 'Debe disparar el evento'])
            ->assertOk();

        // Sin esto no corría ninguno de los siete listeners del módulo:
        // confirmación al cliente, aviso a agentes, automatizaciones,
        // auto-clasificación IA ni auto-asignación.
        Event::assertDispatched(TicketCreated::class);
    }

    public function test_el_ticket_nace_con_el_mensaje_visible_del_cliente(): void
    {
        Event::fake([TicketCreated::class]);

        $id = $this->actingAs($this->gestor)
            ->postJson($this->url(), ['subject' => 'Con cuerpo', 'description' => 'Detalle del problema'])
            ->assertOk()
            ->json('ticket.id');

        $this->assertTrue(
            Ticket::find($id)->items()->where('is_internal', false)->pluck('body')
                ->contains('Detalle del problema'),
            'el hilo del ticket debe arrancar con la descripción, no solo con la nota interna'
        );
    }

    public function test_el_ticket_nace_con_un_estado_del_catalogo(): void
    {
        Event::fake([TicketCreated::class]);

        $id = $this->actingAs($this->gestor)
            ->postJson($this->url(), ['subject' => 'Debe tener estado'])
            ->assertOk()
            ->json('ticket.id');

        // Sin esto el ticket salía con status_id NULL y la fila del listado se
        // quedaba sin etiqueta ni color: el observer solo rellena el estado si
        // hay uno marcado is_default, y aquí no hay ninguno.
        $this->assertNotNull(Ticket::find($id)->status_id);
    }

    // ── Grupo, SLA y rastro en la conversación ────────────────

    public function test_escala_a_un_grupo(): void
    {
        Event::fake([TicketCreated::class]);

        $grupo = TicketGroup::query()->where('is_active', true)->first();

        if (! $grupo) {
            $this->markTestSkipped('no hay grupos configurados');
        }

        $id = $this->actingAs($this->gestor)
            ->postJson($this->url(), ['subject' => 'Para el equipo', 'group_id' => $grupo->id])
            ->assertOk()
            ->json('ticket.id');

        // group_id estaba en $fillable del ticket pero ninguna vía de la bandeja
        // lo rellenaba: solo se podía asignar a una persona concreta.
        $this->assertSame($grupo->id, Ticket::find($id)->group_id);
    }

    public function test_no_acepta_un_grupo_inexistente(): void
    {
        $this->actingAs($this->gestor)
            ->postJson($this->url(), ['subject' => 'Grupo fantasma', 'group_id' => 99999999])
            ->assertStatus(422)
            ->assertJsonValidationErrors('group_id');
    }

    public function test_el_escalado_queda_anotado_en_la_conversacion(): void
    {
        Event::fake([TicketCreated::class]);

        $numero = $this->actingAs($this->gestor)
            ->postJson($this->url(), ['subject' => 'Deja rastro'])
            ->assertOk()
            ->json('ticket.ticket_number');

        // El vínculo era de ida y vuelta a medias: el ticket guardaba
        // conversation_id, pero en el hilo no quedaba rastro del escalado.
        $actividad = ConversationItem::query()
            ->where('conversation_id', $this->conversacion->id)
            ->where('type', 'activity')
            ->where('activity_type', 'ticket_created')
            ->latest('id')
            ->first();

        $this->assertNotNull($actividad, 'debe crearse un item de actividad en la conversación');
        $this->assertStringContainsString($numero, (string) $actividad->body);
        $this->assertSame($numero, $actividad->activity_data['ticket_number'] ?? null);
    }

    public function test_las_categorias_llevan_el_sla_que_heredara_el_ticket(): void
    {
        $categorias = app(TicketServiceContract::class)->getCategories();

        if ($categorias->isEmpty()) {
            $this->markTestSkipped('no hay categorías configuradas');
        }

        // El modal arranca en "Sin categoría" y el agente no tenía forma de
        // saber que así el ticket nace fuera de todos los avisos de SLA.
        $this->assertArrayHasKey('sla', $categorias->first());
    }

    // ── Assets propios del módulo ─────────────────────────────

    public function test_el_modal_carga_el_css_y_el_js_de_su_propio_modulo(): void
    {
        // El CSS (~100 líneas) y el JS (~230) del modal vivían dentro de
        // conversations.css/js, el bundle del CORE Helpdesk: se servían aunque
        // la integración de tickets estuviera apagada. Ahora los publica el
        // propio slot.
        $this->actingAs($this->gestor)
            ->get(route('manager.helpdesk.conversations.index', ['selected' => $this->conversacion->id]))
            ->assertOk()
            ->assertSee('modules/helpdesktickets/css/inbox-create-ticket.css', false)
            ->assertSee('modules/helpdesktickets/js/inbox-create-ticket.js', false);
    }

    // ── Fragmento del panel derecho ───────────────────────────

    public function test_el_tab_de_tickets_se_puede_recargar_solo(): void
    {
        Event::fake([TicketCreated::class]);

        $this->actingAs($this->gestor)
            ->postJson($this->url(), ['subject' => 'Aparece en el panel'])
            ->assertOk();

        // El JS del modal recarga este fragmento tras escalar; sin él, el tab
        // seguía mostrando "Sin tickets relacionados" hasta recargar la bandeja.
        $this->actingAs($this->gestor)
            ->get(route('manager.helpdesk.conversations.right-panel.tickets', $this->conversacion))
            ->assertOk()
            ->assertSee('data-bv-tab-content="tickets"', false)
            ->assertSee('Aparece en el panel');
    }

    // ── Detalle del ticket (IDOR) ─────────────────────────────

    public function test_no_deja_leer_el_detalle_de_un_ticket_ajeno(): void
    {
        $otroCliente = Customer::factory()->create();
        $ajeno = Ticket::create([
            'customer_id' => $otroCliente->id,
            'subject' => 'Ticket de otro cliente',
            'description' => 'Datos que no debe ver',
            'priority' => 'normal',
        ]);

        $this->actingAs($this->gestor)
            ->getJson(route('manager.helpdesk.conversations.ticket-detail', [
                'conversation' => $this->conversacion->id,
                'ticket' => $ajeno->id,
            ]))
            ->assertNotFound();
    }

    public function test_deja_leer_el_detalle_del_ticket_de_la_propia_conversacion(): void
    {
        Event::fake([TicketCreated::class]);

        $id = $this->actingAs($this->gestor)
            ->postJson($this->url(), ['subject' => 'Ticket propio'])
            ->assertOk()
            ->json('ticket.id');

        $this->actingAs($this->gestor)
            ->getJson(route('manager.helpdesk.conversations.ticket-detail', [
                'conversation' => $this->conversacion->id,
                'ticket' => $id,
            ]))
            ->assertOk()
            ->assertJsonPath('ticket.subject', 'Ticket propio');
    }
}
