<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Route;
use Modules\Helpdesk\Models\BusinessHour;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskSla\Models\Holiday;
use Modules\HelpdeskTickets\Http\Controllers\Managers\TicketOpsSlaController;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketSlaPolicy;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Modal "Calendario y SLA" (modal 28) del panel de Gestión.
 *
 * Lo que se comprueba aquí es sobre todo que el modal NO miente: las políticas
 * de esta instalación declaran sus objetivos en las columnas heredadas *_hours,
 * que ningún cálculo lee, mientras el reloj mira las columnas en minutos, que
 * están vacías. El endpoint tiene que distinguir los dos casos (clock_enforced)
 * para que el modal pueda decir "esta política no fija ningún vencimiento" en
 * vez de pintar "resolución 8 h" como si se aplicara.
 *
 * Las rutas se registran aquí a mano porque routes/managers.php lo integra el
 * coordinador (ver informe): las paths y los nombres son exactamente los que se
 * piden registrar.
 */
class TicketOpsSlaTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private User $manager;

    private TicketStatus $status;

    protected function setUp(): void
    {
        parent::setUp();

        // En routes/managers.php estas dos van a /tickets/sla-calendar y
        // /tickets/sla-calendar/pause-status, colocadas ANTES del catch-all
        // Route::get('/tickets/{ticket}'). Aquí cuelgan de /sla-calendar
        // porque las rutas registradas en runtime se añaden al final de la
        // colección: con la ruta real, el catch-all las capturaría primero e
        // intentaría resolver un Ticket llamado "sla-calendar" (404). Los
        // NOMBRES sí son los definitivos, que es lo que consume el JS.
        Route::middleware(['web', 'auth'])->prefix('panel/helpdesk')->group(function () {
            Route::get('/sla-calendar', [TicketOpsSlaController::class, 'calendar'])
                ->name('manager.helpdesk.tickets.sla-calendar');
            Route::post('/sla-calendar/pause-status', [TicketOpsSlaController::class, 'updatePauseStatus'])
                ->name('manager.helpdesk.tickets.sla-calendar.pause-status');
        });

        // RouteServiceProvider refresca el índice de nombres en app->booted();
        // estas rutas se registran DESPUÉS de ese punto, así que sin este
        // refresco route() no las encuentra aunque estén en la colección.
        Route::getRoutes()->refreshNameLookups();

        $this->manager = $this->gestor();

        // Slug propio: los estados sembrados de verdad (new/open/waiting-customer…)
        // tienen id fijo y un firstOrCreate sobre ellos acabaría tocando datos
        // reales aunque la transacción los deshaga.
        $this->status = TicketStatus::create([
            'name' => 'Esperando cliente (test SLA)',
            'slug' => 'test-sla-waiting-'.uniqid(),
            'is_open' => true,
            'stops_sla_timer' => false,
        ]);
    }

    /**
     * @param  array<int, string>  $permissions
     */
    /**
     * Usuario que atraviesa el middleware del grupo de manager.
     *
     * Las rutas cuelgan de `role:super-admin|super-settings`
     * (HelpdeskTicketsServiceProvider::loadManagerRoutes): sin ese rol todo
     * responde 403 antes de llegar al controlador. Mientras el test
     * registraba sus propias rutas ese middleware no existía y bastaba con
     * el permiso suelto.
     */
    private function gestor(): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('super-settings', 'web'));

        return $user;
    }

    private function userWith(array $permissions): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $user = User::factory()->create();

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
            $user->givePermissionTo($permission);
        }

        return $user;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makePolicy(array $attributes = []): TicketSlaPolicy
    {
        $policy = new TicketSlaPolicy;
        $policy->forceFill(array_merge([
            'name' => 'Política de prueba '.uniqid(),
            'active' => true,
            'is_default' => false,
            'timezone' => 'Europe/Madrid',
            'business_hours_only' => false,
        ], $attributes));
        $policy->save();

        return $policy;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeTicket(array $attributes = []): Ticket
    {
        $customer = Customer::create([
            'name' => 'Cliente SLA',
            'email' => 'sla-'.uniqid().'@example.invalid',
        ]);

        return Ticket::create(array_merge([
            'subject' => 'Ticket de calendario SLA',
            'customer_id' => $customer->id,
            'status_id' => $this->status->id,
            'priority' => 'normal',
            'source' => 'email',
        ], $attributes));
    }

    /**
     * @return array<string, mixed>|null
     */
    private function policyFromResponse(array $payload, int $policyId): ?array
    {
        foreach ($payload['policies'] as $row) {
            if ($row['id'] === $policyId) {
                return $row;
            }
        }

        return null;
    }

    // ─── objetivos por prioridad ─────────────────────────────────────────────

    public function test_calcula_los_objetivos_por_prioridad_con_los_multiplicadores_de_la_politica(): void
    {
        $policy = $this->makePolicy([
            'first_response_time' => 120,
            'next_response_time' => 240,
            'resolution_time' => 480,
            'priority_multipliers' => ['urgent' => 0.25, 'high' => 0.5, 'normal' => 1.0, 'low' => 2.0],
        ]);

        $payload = $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.tickets.sla-calendar'))
            ->assertOk()
            ->json();

        $row = $this->policyFromResponse($payload, $policy->id);

        $this->assertNotNull($row);
        $this->assertTrue($row['clock_enforced']);
        $this->assertSame(120, $row['first_response_minutes']);
        $this->assertSame(480, $row['resolution_minutes']);

        $targets = collect($row['targets_by_priority'])->keyBy('priority');

        // Mismo cálculo que Ticket::calculateSlaDueDates(): base × multiplicador.
        $this->assertSame(30, $targets['urgent']['first_response_minutes']);
        $this->assertSame(120, $targets['urgent']['resolution_minutes']);
        $this->assertSame(120, $targets['normal']['first_response_minutes']);
        $this->assertSame(240, $targets['low']['first_response_minutes']);
    }

    public function test_marca_como_sin_efecto_la_politica_que_solo_declara_las_horas_heredadas(): void
    {
        // Es el caso real de las 4 políticas sembradas: *_hours con dato y
        // *_time (minutos, lo que lee el reloj) a NULL.
        $policy = $this->makePolicy([
            'first_response_time' => null,
            'next_response_time' => null,
            'resolution_time' => null,
            'first_response_time_hours' => 4,
            'resolution_time_hours' => 24,
            'priority' => 'critical',
        ]);

        $payload = $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.tickets.sla-calendar'))
            ->assertOk()
            ->json();

        $row = $this->policyFromResponse($payload, $policy->id);

        $this->assertNotNull($row);
        $this->assertFalse($row['clock_enforced']);
        $this->assertSame([], $row['targets_by_priority']);
        $this->assertSame(4, $row['declared_hours']['first_response']);
        $this->assertSame(24, $row['declared_hours']['resolution']);
        // El vocabulario de la columna `priority` de las políticas no es el de
        // los tickets: se etiqueta, no se traduce a una prioridad de ticket.
        $this->assertSame('critical', $row['priority']);
        $this->assertSame('Crítica', $row['priority_label']);
    }

    public function test_publica_el_horario_por_defecto_del_calculo_cuando_la_politica_pide_horas_habiles_sin_definirlas(): void
    {
        $policy = $this->makePolicy([
            'business_hours_only' => true,
            'business_hours' => null,
        ]);

        $payload = $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.tickets.sla-calendar'))
            ->assertOk()
            ->json();

        $row = $this->policyFromResponse($payload, $policy->id);

        $this->assertNotNull($row);
        $this->assertNull($row['business_hours']);
        // L-V 09:00–17:00 escrito dentro de Ticket::calculateBusinessTime().
        $this->assertNotNull($row['business_hours_fallback']);
        $this->assertSame(['start' => '09:00', 'end' => '17:00'], $row['business_hours_fallback']['monday']);
        $this->assertArrayNotHasKey('saturday', $row['business_hours_fallback']);
    }

    public function test_no_devuelve_las_politicas_desactivadas(): void
    {
        $policy = $this->makePolicy(['active' => false, 'first_response_time' => 60]);

        $payload = $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.tickets.sla-calendar'))
            ->assertOk()
            ->json();

        $this->assertNull($this->policyFromResponse($payload, $policy->id));
    }

    // ─── reloj del ticket ────────────────────────────────────────────────────

    public function test_dice_que_el_reloj_del_ticket_esta_pausado_y_desde_cuando(): void
    {
        $ticket = $this->makeTicket([
            'sla_paused_at' => now()->subMinutes(90),
            'sla_paused_duration_minutes' => 45,
            'sla_resolution_due_at' => now()->addHours(3),
        ]);

        $payload = $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.tickets.sla-calendar', ['ticket' => $ticket->id]))
            ->assertOk()
            ->json('ticket');

        $this->assertTrue($payload['paused']);
        $this->assertNotNull($payload['paused_at']);
        $this->assertNotNull($payload['paused_since_human']);
        $this->assertEqualsWithDelta(90, $payload['current_pause_minutes'], 1);
        // El acumulado histórico va aparte de la pausa en curso.
        $this->assertSame(45, $payload['accumulated_pause_minutes']);
        // getEffectiveDueDate() compensa la pausa en curso: el vencimiento
        // efectivo tiene que quedar por detrás del nominal.
        $this->assertTrue($payload['effective_resolution_due_at'] > $payload['due']['resolution']['at']);
    }

    public function test_dice_que_el_reloj_del_ticket_corre_cuando_no_hay_pausa(): void
    {
        $ticket = $this->makeTicket(['sla_resolution_due_at' => now()->addHours(5)]);

        $payload = $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.tickets.sla-calendar', ['ticket' => $ticket->id]))
            ->assertOk()
            ->json('ticket');

        $this->assertFalse($payload['paused']);
        $this->assertNull($payload['paused_at']);
        $this->assertSame(0, $payload['current_pause_minutes']);
        $this->assertSame($payload['due']['resolution']['at'], $payload['effective_resolution_due_at']);
    }

    public function test_avisa_de_que_el_ticket_no_tiene_politica_asignada(): void
    {
        $ticket = $this->makeTicket();

        $payload = $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.tickets.sla-calendar', ['ticket' => $ticket->id]))
            ->assertOk()
            ->json('ticket');

        $this->assertNull($payload['policy']);
        $this->assertSame($this->status->id, $payload['status']['id']);
    }

    public function test_omite_el_bloque_del_ticket_cuando_no_se_pide_ninguno(): void
    {
        $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.tickets.sla-calendar'))
            ->assertOk()
            ->assertJsonPath('ticket', null);
    }

    // ─── pausa automática al esperar al cliente ──────────────────────────────

    public function test_activa_la_pausa_automatica_del_estado_de_espera(): void
    {
        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.sla-calendar.pause-status'), [
                'status_id' => $this->status->id,
                'stops_sla_timer' => true,
            ])
            ->assertOk()
            ->assertJsonPath('status.stops_sla', true);

        $this->assertTrue($this->status->refresh()->stops_sla_timer);
    }

    public function test_desactiva_la_pausa_automatica_del_estado_de_espera(): void
    {
        $this->status->update(['stops_sla_timer' => true]);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.sla-calendar.pause-status'), [
                'status_id' => $this->status->id,
                'stops_sla_timer' => false,
            ])
            ->assertOk()
            ->assertJsonPath('status.stops_sla', false);

        $this->assertFalse($this->status->refresh()->stops_sla_timer);
    }

    public function test_no_toca_los_tickets_que_ya_estaban_en_ese_estado(): void
    {
        // Activar la pausa cambia el catálogo, no el reloj de lo ya parado ahí:
        // desplazar en bloque los vencimientos falsearía el histórico.
        $ticket = $this->makeTicket(['sla_resolution_due_at' => now()->addHours(2)]);
        $due = $ticket->sla_resolution_due_at->toIso8601String();

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.sla-calendar.pause-status'), [
                'status_id' => $this->status->id,
                'stops_sla_timer' => true,
            ])
            ->assertOk();

        $ticket->refresh();

        $this->assertNull($ticket->sla_paused_at);
        $this->assertSame($due, $ticket->sla_resolution_due_at->toIso8601String());
    }

    public function test_quien_no_es_gestor_no_puede_cambiar_la_pausa(): void
    {
        // Corta el middleware del grupo, antes de cualquier policy.
        $agent = $this->userWith(['helpdesk.tickets.view']);

        $this->actingAs($agent)
            ->postJson(route('manager.helpdesk.tickets.sla-calendar.pause-status'), [
                'status_id' => $this->status->id,
                'stops_sla_timer' => true,
            ])
            ->assertForbidden();

        $this->assertFalse($this->status->refresh()->stops_sla_timer);
    }

    public function test_exige_un_estado_existente_para_cambiar_la_pausa(): void
    {
        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.sla-calendar.pause-status'), [
                'status_id' => 999999,
                'stops_sla_timer' => true,
            ])
            ->assertStatus(422);
    }

    public function test_expone_el_catalogo_de_estados_con_su_flag_de_pausa(): void
    {
        $this->status->update(['stops_sla_timer' => true]);

        $payload = $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.tickets.sla-calendar'))
            ->assertOk()
            ->json('pause');

        $this->assertTrue($payload['can_manage']);
        // La reanudación no es configurable: la disparan el cambio de estado y
        // la respuesta del cliente por el portal.
        $this->assertTrue($payload['resume_is_automatic']);

        $mine = collect($payload['statuses'])->firstWhere('id', $this->status->id);

        $this->assertNotNull($mine);
        $this->assertTrue($mine['stops_sla']);
    }

    // ─── horario de la empresa y festivos ────────────────────────────────────

    public function test_publica_la_rejilla_semanal_de_la_empresa_con_su_zona_horaria(): void
    {
        BusinessHour::initializeDefaults();

        $payload = $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.tickets.sla-calendar'))
            ->assertOk()
            ->json('business_hours');

        $this->assertTrue($payload['configured']);
        $this->assertNotNull($payload['timezone']);
        $this->assertCount(7, $payload['days']);
        // El escalado es el único consumidor de esta rejilla dentro del módulo
        // de tickets, y va detrás de su propio toggle.
        $this->assertSame(
            (bool) config('helpdesktickets.escalation.business_hours', false),
            $payload['used_by_escalation']
        );
    }

    public function test_repite_los_festivos_recurrentes_en_su_proxima_aparicion(): void
    {
        $manana = now()->addDay()->startOfDay();

        // Alta en un año pasado y marcado como recurrente: tiene que salir con
        // la fecha del año EN CURSO, no con la del alta.
        Holiday::create([
            'date' => $manana->copy()->setYear(2019)->toDateString(),
            'name' => 'Festivo recurrente de prueba',
            'is_recurring' => true,
        ]);

        $payload = $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.tickets.sla-calendar'))
            ->assertOk()
            ->json('holidays');

        $mio = collect($payload['upcoming'])->firstWhere('name', 'Festivo recurrente de prueba');

        $this->assertNotNull($mio);
        $this->assertSame($manana->toDateString(), $mio['date']);
        $this->assertTrue($mio['is_recurring']);
        $this->assertSame(1, $mio['days_away']);
    }

    public function test_dice_si_los_festivos_afectan_de_verdad_al_reloj_de_tickets(): void
    {
        // Los festivos solo entran en el cálculo dentro de la rama
        // business_hours_only; con una política así activa, sí aplican.
        $this->makePolicy(['business_hours_only' => true, 'first_response_time' => 60]);

        $payload = $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.tickets.sla-calendar'))
            ->assertOk()
            ->json('holidays');

        $this->assertTrue($payload['applies_to_ticket_sla']);
    }

    // ─── enlaces y permisos ──────────────────────────────────────────────────

    public function test_enlaza_a_las_pantallas_donde_se_configura_cada_bloque(): void
    {
        $payload = $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.tickets.sla-calendar'))
            ->assertOk()
            ->json('links');

        $this->assertStringContainsString('sla-policies', (string) $payload['sla_policies']);
        $this->assertStringContainsString('statuses', (string) $payload['statuses']);
        $this->assertStringContainsString('business/hours', (string) $payload['business_hours']);
    }

    public function test_quien_no_es_gestor_no_lee_el_calendario(): void
    {
        // Sin rol de gestión no se pasa del middleware del grupo.
        $extrano = $this->userWith([]);

        $this->actingAs($extrano)
            ->getJson(route('manager.helpdesk.tickets.sla-calendar'))
            ->assertForbidden();
    }
}
