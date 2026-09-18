<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Route;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Http\Controllers\Managers\TicketOpsAutomationsController;
use Modules\HelpdeskTickets\Models\Automation;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Services\AutomationEngine;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Editor "Si… Entonces…" del modal de escalado (pill #tkt-pill-escalation).
 *
 * El modal solo listaba reglas y sacaba al usuario a Ajustes, donde las
 * condiciones y acciones se teclean como JSON crudo. Estas pruebas cubren el
 * endpoint JSON que las crea, alterna y prueba en seco, y sobre todo que no
 * deje guardar nada que AutomationEngine no sepa ejecutar.
 */
class TicketOpsAutomationsTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private User $manager;

    private User $agente;

    private TicketStatus $status;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registrarRutasSiHacenFalta();

        Permission::firstOrCreate(['name' => 'helpdesk.tickets.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'helpdesk.tickets.settings', 'guard_name' => 'web']);

        $rolAjustes = Role::firstOrCreate(['name' => 'super-settings', 'guard_name' => 'web']);
        $rolAjustes->givePermissionTo('helpdesk.tickets.view', 'helpdesk.tickets.settings');

        // Solo lectura: ve las reglas, no puede crearlas ni pausarlas. Rol
        // propio de la prueba: dar permisos a 'helpdesk-agent' cambiaría los
        // de los agentes reales.
        $rolAgente = Role::firstOrCreate(['name' => 'ops-automations-readonly-test', 'guard_name' => 'web']);
        $rolAgente->givePermissionTo('helpdesk.tickets.view');

        $this->manager = User::factory()->create();
        $this->manager->assignRole($rolAjustes);

        $this->agente = User::factory()->create();
        $this->agente->assignRole($rolAgente);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->status = TicketStatus::firstOrCreate(
            ['slug' => 'ops-automations-open'],
            ['name' => 'Abierto (reglas test)']
        );
    }

    // ── Lectura ───────────────────────────────────────────────

    public function test_lista_las_reglas_existentes_con_su_estado(): void
    {
        $regla = $this->crearRegla(['is_active' => false]);

        $rules = $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.tickets.automations.index'))
            ->assertOk()
            ->assertJsonPath('can_manage', true)
            ->json('rules');

        $mia = collect($rules)->firstWhere('id', $regla->id);

        $this->assertNotNull($mia, 'la regla creada debe aparecer en el listado');
        $this->assertFalse($mia['is_active']);
        $this->assertSame('ticket.created', $mia['trigger_event']);
        $this->assertSame([['type' => 'add_tag', 'value' => 'escalado-test']], $mia['actions']);
    }

    /**
     * helpdesk_automations es la misma tabla física que usa el motor de
     * Conversaciones (Modules\Helpdesk\Models\AutomationRule, disparadores
     * conversation.* / message.*). Antes del scope, el modal de escalado las
     * listaba también y las marcaba como "disparador que el motor no
     * conoce", cuando en realidad sí las ejecuta el otro motor. Ver
     * Automation::scopeTicketDomain() (8-sep-2026).
     */
    public function test_una_regla_de_conversaciones_no_aparece_en_el_listado_de_tickets(): void
    {
        $ajena = Automation::create([
            'name' => 'Regla de conversaciones ajena',
            'trigger_event' => 'conversation.created',
            'conditions' => [],
            'actions' => [['type' => 'add_label', 'params' => ['label' => 'x']]],
            'is_active' => true,
            'order' => 999,
        ]);

        $rules = $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.tickets.automations.index'))
            ->assertOk()
            ->json('rules');

        $this->assertNull(collect($rules)->firstWhere('id', $ajena->id));
    }

    public function test_no_se_puede_pausar_una_regla_de_conversaciones_desde_tickets(): void
    {
        $ajena = Automation::create([
            'name' => 'Regla de conversaciones ajena',
            'trigger_event' => 'conversation.created',
            'conditions' => [],
            'actions' => [['type' => 'add_label', 'params' => ['label' => 'x']]],
            'is_active' => true,
            'order' => 999,
        ]);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.automations.toggle', $ajena))
            ->assertNotFound();

        $this->assertTrue($ajena->refresh()->is_active);
    }

    public function test_quien_no_es_gestor_no_llega_a_las_reglas(): void
    {
        // El grupo de rutas de manager exige `role:super-admin|super-settings`
        // (HelpdeskTicketsServiceProvider::loadManagerRoutes), así que un rol
        // de solo lectura se queda en el middleware y nunca ve el `can_manage`
        // del payload. Mientras el test registraba sus propias rutas ese
        // middleware no existía y sí se llegaba al controlador.
        $this->actingAs($this->agente)
            ->getJson(route('manager.helpdesk.tickets.automations.index'))
            ->assertForbidden();
    }

    public function test_el_catalogo_solo_ofrece_acciones_que_el_motor_ejecuta(): void
    {
        $catalogo = $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.tickets.automations.index'))
            ->assertOk()
            ->json('catalog');

        $tipos = array_column($catalogo['actions'], 'type');

        // Los nueve del match de AutomationEngine::runActions(), ni uno más.
        $this->assertEqualsCanonicalizing([
            'assign_group', 'assign_user', 'set_priority', 'set_status', 'add_tag',
            'close', 'add_internal_note', 'notify_agent', 'ai_route',
        ], $tipos);

        // El mockup pedía además estas tres; el motor no las implementa.
        $this->assertEmpty(array_intersect(['send_sla_breach_mail', 'notify_manager', 'escalate_level'], $tipos));

        $this->assertEqualsCanonicalizing(
            array_keys(Automation::$triggerEvents),
            array_column($catalogo['triggers'], 'value')
        );
    }

    // ── Creación ──────────────────────────────────────────────

    public function test_guarda_la_regla_con_sus_condiciones_y_acciones(): void
    {
        $payload = [
            'name' => 'Escalar prioridad alta sin agente',
            'trigger_event' => 'ticket.created',
            'conditions' => [
                ['field' => 'priority', 'op' => 'in', 'value' => ['high', 'urgent']],
                ['field' => 'assignee_id', 'op' => 'is_null', 'value' => null],
            ],
            'actions' => [
                ['type' => 'set_priority', 'value' => 'urgent'],
                ['type' => 'notify_agent', 'value' => null],
            ],
            'is_active' => true,
        ];

        $id = $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.automations.store'), $payload)
            ->assertCreated()
            ->json('rule.id');

        $regla = Automation::findOrFail($id);

        $this->assertSame([
            ['field' => 'priority', 'op' => 'in', 'value' => ['high', 'urgent']],
            ['field' => 'assignee_id', 'op' => 'is_null', 'value' => null],
        ], $regla->conditions);

        $this->assertSame([
            ['type' => 'set_priority', 'value' => 'urgent'],
            ['type' => 'notify_agent', 'value' => null],
        ], $regla->actions);

        $this->assertTrue($regla->is_active);
        $this->assertSame($this->manager->id, (int) $regla->user_id);
    }

    public function test_una_regla_sin_condiciones_vale_para_cualquier_ticket(): void
    {
        // "Cualquier prioridad" del mockup: sin condiciones, el motor la
        // considera siempre cumplida.
        $id = $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.automations.store'), [
                'name' => 'Etiquetar todo lo que entra',
                'trigger_event' => 'ticket.created',
                'conditions' => [],
                'actions' => [['type' => 'add_tag', 'value' => 'entrante']],
            ])
            ->assertCreated()
            ->json('rule.id');

        $this->assertSame([], Automation::findOrFail($id)->conditions);
    }

    public function test_acepta_el_envio_del_formulario_sin_la_clave_condiciones(): void
    {
        // jQuery no serializa los arrays vacíos: cuando la regla no tiene
        // condiciones, la clave no llega. Con la regla 'present' esto
        // devolvía 422 justo en el caso "cualquier ticket".
        $this->actingAs($this->manager)
            ->post(route('manager.helpdesk.tickets.automations.store'), [
                'name' => 'Sin condiciones desde el formulario',
                'trigger_event' => 'ticket.created',
                'actions' => [['type' => 'add_tag', 'value' => 'entrante']],
                'is_active' => 1,
            ], ['Accept' => 'application/json'])
            ->assertCreated();
    }

    public function test_convierte_los_identificadores_a_entero_antes_de_guardar(): void
    {
        // Los <select> mandan "3" (cadena). Guardado tal cual, set_status
        // escribiría una cadena en una columna entera.
        $id = $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.automations.store'), [
                'name' => 'Pasar a estado de prueba',
                'trigger_event' => 'ticket.updated',
                'conditions' => [['field' => 'status_id', 'op' => 'equals', 'value' => (string) $this->status->id]],
                'actions' => [['type' => 'set_status', 'value' => (string) $this->status->id]],
            ])
            ->assertCreated()
            ->json('rule.id');

        $regla = Automation::findOrFail($id);

        $this->assertSame($this->status->id, $regla->conditions[0]['value']);
        $this->assertSame($this->status->id, $regla->actions[0]['value']);
    }

    // ── Validación ────────────────────────────────────────────

    public function test_rechaza_una_accion_que_el_motor_no_implementa(): void
    {
        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.automations.store'), [
                'name' => 'Avisar por correo a los managers',
                'trigger_event' => 'ticket.created',
                'conditions' => [],
                'actions' => [['type' => 'send_sla_breach_mail', 'value' => 'managers']],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('actions.0.type');
    }

    public function test_rechaza_un_operador_que_no_vale_para_ese_campo(): void
    {
        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.automations.store'), [
                'name' => 'Asunto exacto',
                'trigger_event' => 'ticket.created',
                // El asunto solo admite contiene / no contiene.
                'conditions' => [['field' => 'subject', 'op' => 'greater_than', 'value' => 'x']],
                'actions' => [['type' => 'add_tag', 'value' => 'x']],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('conditions.0.op');
    }

    public function test_rechaza_un_estado_que_ya_no_existe(): void
    {
        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.automations.store'), [
                'name' => 'Estado fantasma',
                'trigger_event' => 'ticket.created',
                'conditions' => [],
                'actions' => [['type' => 'set_status', 'value' => 99999999]],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('actions.0.value');
    }

    public function test_rechaza_una_regla_sin_acciones(): void
    {
        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.automations.store'), [
                'name' => 'Regla que no hace nada',
                'trigger_event' => 'ticket.created',
                'conditions' => [],
                'actions' => [],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('actions');
    }

    public function test_sin_permiso_de_ajustes_no_se_pueden_crear_reglas(): void
    {
        $this->actingAs($this->agente)
            ->postJson(route('manager.helpdesk.tickets.automations.store'), [
                'name' => 'Intento sin permiso',
                'trigger_event' => 'ticket.created',
                'conditions' => [],
                'actions' => [['type' => 'add_tag', 'value' => 'x']],
            ])
            ->assertForbidden();
    }

    // ── Activar / pausar ──────────────────────────────────────

    public function test_alterna_el_estado_de_una_regla(): void
    {
        $regla = $this->crearRegla(['is_active' => true]);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.automations.toggle', $regla))
            ->assertOk()
            ->assertJsonPath('rule.is_active', false);

        $this->assertFalse($regla->refresh()->is_active);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.automations.toggle', $regla))
            ->assertOk()
            ->assertJsonPath('rule.is_active', true);
    }

    public function test_sin_permiso_de_ajustes_no_se_puede_pausar_una_regla(): void
    {
        $regla = $this->crearRegla(['is_active' => true]);

        $this->actingAs($this->agente)
            ->postJson(route('manager.helpdesk.tickets.automations.toggle', $regla))
            ->assertForbidden();

        $this->assertTrue($regla->refresh()->is_active);
    }

    // ── Probar regla ──────────────────────────────────────────

    public function test_la_prueba_en_seco_cuenta_los_tickets_que_coincidirian(): void
    {
        $ticket = $this->crearTicket('urgent');

        $respuesta = $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.automations.preview'), [
                'conditions' => [
                    ['field' => 'priority', 'op' => 'in', 'value' => ['high', 'urgent']],
                    ['field' => 'subject', 'op' => 'contains', 'value' => 'Zarandaja de escalado'],
                ],
            ])
            ->assertOk()
            ->json();

        $this->assertSame(1, $respuesta['matched']);
        $this->assertSame($ticket->ticket_number, $respuesta['sample'][0]['ticket_number']);
    }

    public function test_la_prueba_en_seco_no_toca_los_tickets(): void
    {
        $ticket = $this->crearTicket('urgent');

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.automations.preview'), [
                'conditions' => [['field' => 'subject', 'op' => 'contains', 'value' => 'Zarandaja de escalado']],
            ])
            ->assertOk();

        $ticket->refresh();

        $this->assertSame('urgent', $ticket->priority);
        $this->assertNull($ticket->closed_at);
        $this->assertSame(0, $ticket->items()->count());
    }

    // ── El motor entiende lo que guarda el modal ──────────────

    public function test_el_motor_ejecuta_una_regla_creada_desde_el_modal(): void
    {
        // ticket.resolved no tiene reglas reales en esta instalación; si
        // alguien crea una, el motor las ejecutaría todas contra el ticket de
        // prueba (correos incluidos), así que la prueba se salta.
        $ajenas = Automation::query()
            ->where('trigger_event', 'ticket.resolved')
            ->where('is_active', true)
            ->count();

        if ($ajenas > 0) {
            $this->markTestSkipped('Hay reglas reales activas en ticket.resolved: no se ejecuta el motor sobre la BD real.');
        }

        $id = $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.automations.store'), [
                'name' => 'Etiquetar los urgentes al resolverse',
                'trigger_event' => 'ticket.resolved',
                'conditions' => [['field' => 'priority', 'op' => 'in', 'value' => ['high', 'urgent']]],
                'actions' => [['type' => 'add_tag', 'value' => 'escalado-verificado']],
            ])
            ->assertCreated()
            ->json('rule.id');

        $ticket = $this->crearTicket('urgent');

        app(AutomationEngine::class)->handle('ticket.resolved', $ticket);

        $this->assertContains('escalado-verificado', $ticket->refresh()->tags ?? []);
        $this->assertSame(1, (int) Automation::findOrFail($id)->run_count);
    }

    // ── Utilidades ────────────────────────────────────────────

    private function crearRegla(array $attrs = []): Automation
    {
        return Automation::create(array_merge([
            'name' => 'Regla de prueba '.uniqid(),
            'trigger_event' => 'ticket.created',
            'conditions' => [['field' => 'priority', 'op' => 'equals', 'value' => 'urgent']],
            'actions' => [['type' => 'add_tag', 'value' => 'escalado-test']],
            'is_active' => true,
            'order' => 999,
        ], $attrs));
    }

    private function crearTicket(string $priority): Ticket
    {
        $customer = Customer::create([
            'name' => 'Cliente Escalado',
            'email' => 'escalado-'.uniqid().'@example.invalid',
        ]);

        return Ticket::create([
            'subject' => 'Zarandaja de escalado '.uniqid(),
            'customer_id' => $customer->id,
            'status_id' => $this->status->id,
            'priority' => $priority,
            'source' => 'email',
        ]);
    }

    /**
     * Las rutas definitivas las añade el coordinador a routes/managers.php
     * (van en el informe con su nombre exacto). Mientras no estén, la prueba
     * las declara igual para poder probar el controlador de extremo a extremo;
     * cuando existan, este método no hace nada.
     */
    private function registrarRutasSiHacenFalta(): void
    {
        if (Route::has('manager.helpdesk.tickets.automations.index')) {
            return;
        }

        Route::middleware(['web', 'auth'])->prefix('panel/helpdesk')->group(function () {
            Route::get('/tickets/ops/automations', [TicketOpsAutomationsController::class, 'index'])
                ->name('manager.helpdesk.tickets.automations.index');
            Route::post('/tickets/ops/automations', [TicketOpsAutomationsController::class, 'store'])
                ->name('manager.helpdesk.tickets.automations.store');
            Route::post('/tickets/ops/automations/preview', [TicketOpsAutomationsController::class, 'preview'])
                ->name('manager.helpdesk.tickets.automations.preview');
            Route::post('/tickets/ops/automations/{automation}/toggle', [TicketOpsAutomationsController::class, 'toggle'])
                ->name('manager.helpdesk.tickets.automations.toggle');
        });

        // RouteServiceProvider solo refresca la lista de nombres al arrancar
        // (app->booted): una ruta añadida después existe pero route() no la
        // encuentra por nombre hasta refrescarla a mano.
        Route::getRoutes()->refreshNameLookups();
    }
}
