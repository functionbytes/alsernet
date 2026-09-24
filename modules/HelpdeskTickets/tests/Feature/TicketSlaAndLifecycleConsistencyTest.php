<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Events\MessageAdded;
use Modules\HelpdeskTickets\Events\SlaBreached;
use Modules\HelpdeskTickets\Events\TicketClosed;
use Modules\HelpdeskTickets\Events\TicketCreated;
use Modules\HelpdeskTickets\Events\TicketSlaBreached;
use Modules\HelpdeskTickets\Events\TicketStatusChanged;
use Modules\HelpdeskTickets\Listeners\RunAutomationsOnTicketActivity;
use Modules\HelpdeskTickets\Listeners\TrackTicketResponseSla;
use Modules\HelpdeskTickets\Models\Automation;
use Modules\HelpdeskTickets\Models\Macro;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketGroup;
use Modules\HelpdeskTickets\Models\TicketSlaBreach;
use Modules\HelpdeskTickets\Models\TicketSlaPolicy;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Services\AssignmentService;
use Modules\HelpdeskTickets\Services\AutomationEngine;
use Modules\HelpdeskTickets\Services\EscalationService;
use Modules\HelpdeskTickets\Services\MacroExecutor;
use Modules\HelpdeskTickets\Services\SlaService;
use Modules\HelpdeskTickets\Services\TicketUpdateService;
use Modules\HelpdeskTickets\Tests\Concerns\SharesHelpdeskPdo;
use Tests\Concerns\SeedsHelpdeskRoles;
use Tests\TestCase;

/**
 * Bugs de coherencia de SLA y ciclo de vida corregidos el 24-sep-2026:
 * plazo de siguiente respuesta fijo desde el alta, prioridad que no movía el
 * SLA, pospuestos que no despertaban, reabrir a "Nuevo", macros con update()
 * directos y bucle infinito del horario laboral.
 */
class TicketSlaAndLifecycleConsistencyTest extends TestCase
{
    use SeedsHelpdeskRoles;
    use SharesHelpdeskPdo;

    private Customer $customer;

    private TicketSlaPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::firstOrCreate(
            ['email' => 'sla-consistency@example.com'],
            ['name' => 'SLA Consistency'],
        );

        $this->policy = TicketSlaPolicy::create([
            'name' => 'SLA test '.uniqid(),
            'first_response_time' => 60,
            'next_response_time' => 120,
            'resolution_time' => 1440,
            'business_hours_only' => false,
            'priority_multipliers' => ['urgent' => 0.25, 'high' => 0.5, 'normal' => 1.0, 'low' => 2.0],
            'active' => true,
            'is_default' => false,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_un_ticket_nuevo_no_tiene_plazo_de_siguiente_respuesta(): void
    {
        $ticket = $this->ticket();
        $ticket->calculateSlaDueDates();

        $this->assertNull($ticket->fresh()->sla_next_response_due_at);
    }

    public function test_la_replica_del_cliente_abre_el_plazo_y_la_respuesta_del_agente_lo_cierra(): void
    {
        $agent = User::factory()->create();
        $ticket = $this->ticket(['first_response_at' => now()->subHour()]);

        Carbon::setTestNow(now()->startOfMinute());
        $this->track($ticket->items()->create([
            'type' => 'message', 'author_id' => $this->customer->id, 'body' => 'hola', 'is_internal' => false,
        ]));

        $this->assertEquals(now()->addMinutes(120), $ticket->fresh()->sla_next_response_due_at);

        $this->track($ticket->items()->create([
            'type' => 'message', 'user_id' => $agent->id, 'body' => 'respuesta', 'is_internal' => false,
        ]));

        $this->assertNull($ticket->fresh()->sla_next_response_due_at);
    }

    public function test_la_primera_respuesta_la_marca_cualquier_respuesta_publica_de_agente_y_no_una_nota(): void
    {
        $agent = User::factory()->create();
        $ticket = $this->ticket();

        $this->track($ticket->items()->create([
            'type' => 'message', 'user_id' => $agent->id, 'body' => 'nota', 'is_internal' => true,
        ]));
        $this->assertNull($ticket->fresh()->first_response_at);

        $this->track($ticket->items()->create([
            'type' => 'message', 'user_id' => $agent->id, 'body' => 'respuesta', 'is_internal' => false,
        ]));
        $this->assertNotNull($ticket->fresh()->first_response_at);
    }

    public function test_la_replica_del_cliente_despierta_un_ticket_pospuesto(): void
    {
        $ticket = $this->ticket(['snoozed_until' => now()->addDays(3)]);

        $this->track($ticket->items()->create([
            'type' => 'message', 'author_id' => $this->customer->id, 'body' => 'sigo esperando', 'is_internal' => false,
        ]));

        $this->assertNull($ticket->fresh()->snoozed_until);
    }

    public function test_cambiar_la_prioridad_recalcula_el_sla_desde_el_alta(): void
    {
        $agent = User::factory()->create();
        $ticket = $this->ticket();
        $ticket->calculateSlaDueDates();
        $createdAt = $ticket->fresh()->created_at;

        app(TicketUpdateService::class)->applyChanges($ticket, ['priority' => 'urgent'], $agent);

        // resolución 1440 min × 0,25 = 360 min desde el alta, no desde ahora.
        $this->assertEquals(
            $createdAt->copy()->addMinutes(360)->toDateTimeString(),
            $ticket->fresh()->sla_resolution_due_at->toDateTimeString(),
        );
    }

    public function test_reabrir_deja_el_ticket_en_reabierto_y_no_en_nuevo(): void
    {
        $reopened = TicketStatus::firstOrCreate(
            ['slug' => 'reopened'],
            ['name' => 'Reabierto', 'color' => '#90bb13', 'is_open' => true, 'is_default' => false, 'order' => 7],
        );
        Cache::forget('helpdesk:reopened-status');

        $ticket = $this->ticket(['closed_at' => now(), 'sla_resolution_breached' => true]);
        $ticket->reopen();

        $this->assertSame($reopened->id, $ticket->fresh()->status_id);
        $this->assertFalse((bool) $ticket->fresh()->sla_resolution_breached);
    }

    public function test_la_macro_cerrar_cierra_de_verdad_y_dispara_los_eventos(): void
    {
        Event::fake([TicketClosed::class, TicketStatusChanged::class]);

        $closed = TicketStatus::firstOrCreate(
            ['slug' => 'closed'],
            ['name' => 'Cerrado', 'color' => '#90bb13', 'is_open' => false, 'is_default' => false, 'order' => 8],
        );
        Cache::forget('helpdesk:closed-status');

        $open = TicketStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Abierto', 'color' => '#90bb13', 'is_open' => true, 'is_default' => false, 'order' => 2],
        );

        $this->actingAs(User::factory()->create());
        $ticket = $this->ticket(['status_id' => $open->id]);

        $macro = Macro::create([
            'name' => 'Cerrar '.uniqid(),
            'actions' => [['type' => 'close']],
            'is_active' => true,
        ]);

        app(MacroExecutor::class)->run($macro, $ticket);

        $this->assertSame($closed->id, $ticket->fresh()->status_id);
        Event::assertDispatched(TicketClosed::class);
        Event::assertDispatched(TicketStatusChanged::class);
    }

    public function test_el_horario_laboral_vacio_no_cuelga_el_calculo(): void
    {
        $this->policy->update(['business_hours_only' => true, 'business_hours' => []]);
        $ticket = $this->ticket();

        $ticket->calculateSlaDueDates();

        $this->assertNotNull($ticket->fresh()->sla_resolution_due_at);
    }

    public function test_el_alta_desde_el_portal_dispara_ticket_created(): void
    {
        Event::fake([TicketCreated::class]);

        $this->withSession(['portal_customer_id' => $this->customer->id]);

        $this->post(route('portal.tickets.store'), [
            'subject' => 'Alta portal '.uniqid(),
            'description' => 'Necesito ayuda',
            'priority' => 'high',
        ]);

        Event::assertDispatched(TicketCreated::class);
    }

    public function test_el_escalado_no_sube_la_prioridad_de_un_ticket_resuelto_ni_pospuesto(): void
    {
        // El barrido recorre también los tickets reales: sin esto mandaba
        // correos de escalado de verdad.
        Mail::fake();
        config([
            'helpdesktickets.escalation.mode' => 'priority',
            'helpdesk.escalation.enabled' => true,
            'helpdesktickets.escalation.sla_enabled' => true,
            'helpdesktickets.escalation.notify_managers' => false,
        ]);

        $vencido = [
            'created_at' => now()->subHours(2),
            'sla_resolution_due_at' => now()->subHour(),
            'sla_resolution_breached' => true,
        ];

        $resuelto = $this->ticket($vencido + ['resolved_at' => now()->subMinutes(30)]);
        $pospuesto = $this->ticket($vencido + ['snoozed_until' => now()->addDay()]);

        app(EscalationService::class)->checkAndEscalate();

        $this->assertSame('normal', $resuelto->fresh()->priority);
        $this->assertSame('normal', $pospuesto->fresh()->priority);
    }

    public function test_un_interruptor_apagado_bloquea_tambien_el_endpoint(): void
    {
        $previous = Cache::get('helpdesk_ticket_features');

        try {
            // La caché del helper vive en el Redis real: se restaura siempre.
            Cache::put('helpdesk_ticket_features', ['ticket_features.feature_action_snooze_enabled' => false], 60);

            $this->seedHelpdeskRoles();
            $manager = User::factory()->create();
            $manager->assignRole('super-settings');
            $ticket = $this->ticket();

            $this->actingAs($manager)
                ->postJson(route('manager.helpdesk.tickets.snooze', $ticket), ['snoozed_until' => now()->addDay()->toIso8601String()])
                ->assertForbidden();
        } finally {
            $previous === null
                ? Cache::forget('helpdesk_ticket_features')
                : Cache::put('helpdesk_ticket_features', $previous, now()->addMinutes(10));
        }
    }

    public function test_la_respuesta_del_cliente_dispara_su_automatizacion_y_la_del_agente_no(): void
    {
        $automation = Automation::create([
            'name' => 'Cliente insiste '.uniqid(),
            'trigger_event' => 'ticket.customer_replied',
            'conditions' => [],
            'actions' => [['type' => 'add_tag', 'value' => 'cliente-insiste']],
            'is_active' => true,
            'order' => 0,
        ]);

        $ticket = $this->ticket();
        $listener = app(RunAutomationsOnTicketActivity::class);

        $agentItem = $ticket->items()->create(['type' => 'message', 'user_id' => User::factory()->create()->id, 'body' => 'a', 'is_internal' => false]);
        $listener->handle(new MessageAdded($agentItem));
        $this->assertNotContains('cliente-insiste', $ticket->fresh()->tags ?? []);

        $customerItem = $ticket->items()->create(['type' => 'message', 'author_id' => $this->customer->id, 'body' => 'b', 'is_internal' => false]);
        $listener->handle(new MessageAdded($customerItem));
        $this->assertContains('cliente-insiste', $ticket->fresh()->tags ?? []);

        Cache::forget("helpdesk:automation-runs:{$automation->id}:{$ticket->id}");
    }

    public function test_la_busqueda_encuentra_por_email_del_cliente(): void
    {
        $ticket = $this->ticket();

        $ids = Ticket::query()->where('id', '>', 0)->search('sla-consistency@example.com')->pluck('id');

        $this->assertContains($ticket->id, $ids);
    }

    public function test_el_reparto_prefiere_a_los_miembros_del_equipo_del_ticket(): void
    {
        $miembro = User::factory()->create();
        $ajeno = User::factory()->create();
        $equipo = TicketGroup::create(['name' => 'Equipo reparto '.uniqid()]);
        DB::connection('helpdesk')->table('helpdesk_group_user')->insert([
            'group_id' => $equipo->id, 'user_id' => $miembro->id,
        ]);

        $ticket = $this->ticket(['group_id' => $equipo->id]);
        $method = new \ReflectionMethod(AssignmentService::class, 'preferTeamMembers');

        $picked = $method->invoke(app(AssignmentService::class), collect([$ajeno, $miembro]), $ticket);
        $this->assertSame([$miembro->id], $picked->pluck('id')->all());

        // Nadie del equipo disponible: se mantiene el grupo completo.
        $fallback = $method->invoke(app(AssignmentService::class), collect([$ajeno]), $ticket);
        $this->assertSame([$ajeno->id], $fallback->pluck('id')->all());
    }

    public function test_una_regla_por_tiempo_actua_una_vez_por_periodo_de_inactividad(): void
    {
        $automation = Automation::create([
            'name' => 'Parado 48 h '.uniqid(),
            'trigger_event' => 'ticket.time_elapsed',
            'conditions' => [['field' => 'hours_since_last_activity', 'op' => 'greater_than', 'value' => 48]],
            'actions' => [['type' => 'add_tag', 'value' => 'parado']],
            'is_active' => true,
            'order' => 0,
        ]);

        $parado = $this->ticket(['last_activity_at' => now()->subHours(50)]);
        $activo = $this->ticket(['last_activity_at' => now()->subHour()]);
        $engine = app(AutomationEngine::class);

        $this->assertSame(1, $engine->runTimeBased(collect([$automation]), $parado->fresh()));
        $this->assertSame(0, $engine->runTimeBased(collect([$automation]), $parado->fresh()), 'No se repite en el mismo periodo.');
        $this->assertSame(0, $engine->runTimeBased(collect([$automation]), $activo->fresh()));
        $this->assertContains('parado', $parado->fresh()->tags ?? []);

        Cache::forget('helpdesk:automation-time:'.$automation->id.':'.$parado->id.':'.$parado->fresh()->last_activity_at->getTimestamp());
    }

    public function test_las_condiciones_pueden_combinarse_con_cualquiera(): void
    {
        $ticket = new Ticket(['priority' => 'high', 'subject' => 'Consulta']);
        $conditions = [
            ['field' => 'priority', 'op' => 'equals', 'value' => 'urgent'],
            ['field' => 'subject', 'op' => 'contains', 'value' => 'consulta'],
        ];
        $engine = app(AutomationEngine::class);

        $this->assertFalse($engine->matchesConditions($conditions, $ticket, 'all'));
        $this->assertTrue($engine->matchesConditions($conditions, $ticket, 'any'));
        $this->assertTrue($engine->matchesConditions([], $ticket, 'any'), 'Sin condiciones vale para cualquier ticket.');
    }

    public function test_la_politica_mas_especifica_gana_por_prioridad_y_vip(): void
    {
        $general = TicketSlaPolicy::create(['name' => 'General '.uniqid(), 'first_response_time' => 60, 'resolution_time' => 1440, 'active' => true, 'is_default' => false]);
        $urgente = TicketSlaPolicy::create(['name' => 'Urgente '.uniqid(), 'applies_to_priority' => 'urgent', 'first_response_time' => 15, 'resolution_time' => 240, 'active' => true, 'is_default' => false]);
        $urgenteVip = TicketSlaPolicy::create(['name' => 'Urgente VIP '.uniqid(), 'applies_to_priority' => 'urgent', 'applies_to_vip' => true, 'first_response_time' => 5, 'resolution_time' => 120, 'active' => true, 'is_default' => false]);

        $ticket = new Ticket(['priority' => 'urgent', 'source' => 'web', 'customer_id' => $this->customer->id]);
        $ticket->setRelation('customer', $this->customer->forceFill(['is_vip' => false]));
        $this->assertSame($urgente->id, TicketSlaPolicy::resolveForTicket($ticket)?->id);

        $ticket->setRelation('customer', $this->customer->forceFill(['is_vip' => true]));
        $this->assertSame($urgenteVip->id, TicketSlaPolicy::resolveForTicket($ticket)?->id);

        $ticket->priority = 'low';
        $this->assertNotContains(TicketSlaPolicy::resolveForTicket($ticket)?->id, [$urgente->id, $urgenteVip->id]);
        $this->assertNotNull($general);
    }

    public function test_el_barrido_de_sla_dispara_la_alerta_del_panel_y_de_equipo(): void
    {
        Event::fake([TicketSlaBreached::class, SlaBreached::class]);

        $ticket = $this->ticket(['sla_resolution_due_at' => now()->subHour(), 'sla_resolution_breached' => false]);

        app(SlaService::class)->checkBreaches();

        Event::assertDispatched(TicketSlaBreached::class, fn ($e) => $e->ticket->id === $ticket->id);
        $this->assertSame(1, TicketSlaBreach::query()->where('ticket_id', $ticket->id)->count());
    }

    public function test_detener_aqui_evita_que_se_evaluen_las_reglas_siguientes(): void
    {
        $primera = Automation::create([
            'name' => 'Primera '.uniqid(), 'trigger_event' => 'ticket.reopened', 'conditions' => [],
            'actions' => [['type' => 'add_tag', 'value' => 'primera'], ['type' => 'stop_processing']],
            'is_active' => true, 'order' => -2,
        ]);
        $segunda = Automation::create([
            'name' => 'Segunda '.uniqid(), 'trigger_event' => 'ticket.reopened', 'conditions' => [],
            'actions' => [['type' => 'add_tag', 'value' => 'segunda']],
            'is_active' => true, 'order' => -1,
        ]);
        $ticket = $this->ticket();

        app(AutomationEngine::class)->handle('ticket.reopened', $ticket);

        $tags = $ticket->fresh()->tags ?? [];
        $this->assertContains('primera', $tags);
        $this->assertNotContains('segunda', $tags);

        Cache::forget("helpdesk:automation-runs:{$primera->id}:{$ticket->id}");
        Cache::forget("helpdesk:automation-runs:{$segunda->id}:{$ticket->id}");
    }

    private function ticket(array $overrides = []): Ticket
    {
        return Ticket::create(array_merge([
            'subject' => 'SLA '.uniqid(),
            'description' => 'x',
            'customer_id' => $this->customer->id,
            'priority' => 'normal',
            'source' => 'web',
            'sla_policy_id' => $this->policy->id,
        ], $overrides));
    }

    private function track($item): void
    {
        (new TrackTicketResponseSla)->handle(new MessageAdded($item));
    }
}
