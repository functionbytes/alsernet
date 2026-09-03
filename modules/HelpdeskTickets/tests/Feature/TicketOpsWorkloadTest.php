<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Setting;
use Modules\Helpdesk\Services\AutoAssignmentService;
use Modules\HelpdeskTickets\Http\Controllers\Managers\TicketOpsWorkloadController;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Tests\Concerns\SharesHelpdeskPdo;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Modal 45 "Carga de agentes": resumen de carga por agente/equipo y ajustes
 * del reparto automático (TicketOpsWorkloadController).
 *
 * Las cifras se comprueban SIEMPRE por delta o buscando la fila del agente
 * creado en el test: el endpoint cuenta toda la tabla y el entorno de tests es
 * la base real, con tickets previos que no son de este test.
 */
class TicketOpsWorkloadTest extends TestCase
{
    use SharesHelpdeskPdo;

    /** Claves de ajuste que el endpoint lee/escribe y que se cachean fuera de la transacción. */
    private const SETTING_KEYS = [
        'auto_assign.enabled',
        'auto_assign.strategy',
        'auto_assign.retry',
        'auto_assign.fallback',
        'auto_assign.language_routing',
    ];

    private TicketStatus $openStatus;

    private Customer $customer;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->helpdeskConnectionAvailable()) {
            $this->markTestSkipped('Helpdesk database connection is not available.');
        }

        $this->registerRoutes();
        $this->forgetSettingCache();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::findOrCreate('helpdesk-agent', 'web');

        // Los ajustes de auto_assign son globales: se limpian para partir del
        // valor por defecto del fichero de config (la transacción los revierte).
        Setting::query()->where('key', 'like', 'auto_assign.%')->delete();

        $this->openStatus = TicketStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#90bb13', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );

        $this->customer = Customer::create([
            'name' => 'Workload Customer',
            'email' => 'workload-modal@example.com',
        ]);

        $this->manager = $this->manager();
    }

    protected function tearDown(): void
    {
        // Setting::get() cachea en Redis, que NO entra en el rollback: sin esto
        // el valor escrito por el test sobreviviría al test.
        $this->forgetSettingCache();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        parent::tearDown();
    }

    // ─── carga por agente ────────────────────────────────────────────────

    public function test_cuenta_solo_los_tickets_abiertos_de_cada_agente(): void
    {
        $agent = $this->createAgent('Carga', 'workload-open@example.com');

        $this->makeTicket($agent->id);
        $this->makeTicket($agent->id);
        $this->makeTicket($agent->id, ['closed_at' => now()]);

        $row = $this->agentRow($this->overview(), $agent->id);

        $this->assertSame(2, $row['open_tickets']);
        $this->assertTrue($row['eligible']);
    }

    public function test_el_sla_incumplido_de_un_ticket_cerrado_no_cuenta_como_en_riesgo(): void
    {
        $agent = $this->createAgent('Riesgo', 'workload-risk@example.com');

        $this->makeTicket($agent->id, ['sla_resolution_breached' => true]);
        // Las banderas sla_*_breached no se limpian al cerrar: sin el filtro
        // por closed_at este ticket inflaría "en riesgo" para siempre.
        $this->makeTicket($agent->id, ['sla_resolution_breached' => true, 'closed_at' => now()]);

        $row = $this->agentRow($this->overview(), $agent->id);

        $this->assertSame(1, $row['open_tickets']);
        $this->assertSame(1, $row['at_risk']);
    }

    public function test_lista_al_agente_con_carga_aunque_ya_no_reciba_reparto(): void
    {
        $agent = $this->createAgent('Ausente', 'workload-away@example.com', ['available' => false]);

        $this->makeTicket($agent->id);

        $row = $this->agentRow($this->overview(), $agent->id);

        $this->assertSame(1, $row['open_tickets']);
        $this->assertFalse($row['eligible'], 'Un agente no disponible debe salir marcado como que no recibe reparto.');
    }

    public function test_agrupa_la_carga_por_equipo_real(): void
    {
        $agent = $this->createAgent('Equipo', 'workload-team@example.com');
        $groupId = $this->createGroupWithAgent('Equipo de prueba carga', $agent->id);

        $this->makeTicket($agent->id);
        $this->makeTicket($agent->id, ['sla_resolution_breached' => true]);

        $data = $this->overview();
        $team = collect($data['teams'])->firstWhere('id', $groupId);

        $this->assertNotNull($team, 'El equipo del agente debe aparecer en la vista por equipos.');
        $this->assertSame(2, $team['open_tickets']);
        $this->assertSame(1, $team['at_risk']);
        $this->assertSame(1, $team['agents']);
        $this->assertContains($agent->id, $team['agent_ids']);

        $this->assertContains($groupId, $this->agentRow($data, $agent->id)['team_ids']);
    }

    public function test_no_devuelve_capacidad_por_agente_porque_no_existe_el_dato(): void
    {
        // El mockup pide "87 %" y "capacidad por agente". No hay ninguna
        // capacidad de tickets configurada, así que el endpoint la declara
        // ausente en vez de calcular un porcentaje inventado.
        $this->assertNull($this->overview()['capacity']);
    }

    // ─── tickets sin asignar ─────────────────────────────────────────────

    public function test_el_desglose_de_sin_asignar_separa_los_cerrados(): void
    {
        $antes = $this->overview()['unassigned'];

        $this->makeTicket(null);
        $this->makeTicket(null, ['closed_at' => now()]);

        $despues = $this->overview()['unassigned'];

        $this->assertSame($antes['total'] + 2, $despues['total']);
        $this->assertSame($antes['open'] + 1, $despues['open']);
        $this->assertSame($antes['closed'] + 1, $despues['closed']);
    }

    public function test_los_pospuestos_no_entran_en_el_reparto(): void
    {
        $antes = $this->overview()['unassigned']['total'];

        // Mismo criterio que el endpoint de reparto: notSnoozed().
        $this->makeTicket(null, ['snoozed_until' => now()->addDay()]);

        $this->assertSame($antes, $this->overview()['unassigned']['total']);
    }

    // ─── ajustes del reparto automático ──────────────────────────────────

    public function test_devuelve_el_interruptor_y_la_estrategia_configurados(): void
    {
        Setting::set('auto_assign.enabled', '1', 'auto_assign');
        Setting::set('auto_assign.strategy', 'least_load', 'auto_assign');

        $assignment = $this->overview()['assignment'];

        $this->assertTrue($assignment['enabled']);
        $this->assertSame('least_load', $assignment['strategy']);
        $this->assertSame(
            ['round_robin', 'least_load', 'manual'],
            array_column($assignment['strategies'], 'value')
        );
        // El botón de repartir llama siempre a autoAssignByWorkload(), tenga la
        // estrategia global la que tenga: el modal lo dice con este dato.
        $this->assertSame('least_load', $assignment['manual_distribution_strategy']);
    }

    public function test_guarda_el_interruptor_y_la_estrategia_del_reparto(): void
    {
        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.workload.assignment'), [
                'enabled' => 1,
                'strategy' => 'least_load',
                'language_routing' => 1,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $config = app(AutoAssignmentService::class)->config();

        $this->assertTrue($config['enabled']);
        $this->assertSame('least_load', $config['strategy']);
        $this->assertTrue($this->overview()['assignment']['language_routing']);
    }

    public function test_guardar_no_pisa_el_reintento_ni_el_fallback_de_conversaciones(): void
    {
        // retry/fallback solo afectan a conversaciones y el modal de tickets no
        // los muestra: guardarlos a ciegas cambiaría otro módulo.
        Setting::set('auto_assign.retry', '15', 'auto_assign');
        Setting::set('auto_assign.fallback', 'supervisor', 'auto_assign');

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.workload.assignment'), [
                'enabled' => 0,
                'strategy' => 'manual',
                'language_routing' => 0,
            ])
            ->assertOk();

        $config = app(AutoAssignmentService::class)->config();

        $this->assertSame('15', $config['retry']);
        $this->assertSame('supervisor', $config['fallback']);
        $this->assertSame('manual', $config['strategy']);
    }

    public function test_rechaza_una_estrategia_que_no_existe(): void
    {
        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.workload.assignment'), [
                'enabled' => 1,
                'strategy' => 'por_simpatia',
                'language_routing' => 0,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('strategy');
    }

    // ─── permisos ────────────────────────────────────────────────────────

    public function test_sin_permiso_de_ajustes_no_se_puede_cambiar_la_estrategia(): void
    {
        // Sin rol de gestión no se pasa del middleware del grupo.
        $agente = $this->userWithPermissions(['helpdesk.tickets.view', 'helpdesk.tickets.update']);

        $this->actingAs($agente)
            ->postJson(route('manager.helpdesk.tickets.workload.assignment'), [
                'enabled' => 1,
                'strategy' => 'manual',
                'language_routing' => 0,
            ])
            ->assertForbidden();
    }

    public function test_quien_no_es_gestor_no_llega_a_la_carga(): void
    {
        // Corta el middleware del grupo, antes que ninguna policy: un agente
        // con permisos de ticket pero sin rol de gestión no entra.
        $this->actingAs($this->userWithPermissions(['helpdesk.tickets.view']))
            ->getJson(route('manager.helpdesk.tickets.workload.overview'))
            ->assertForbidden();
    }

    public function test_el_resumen_dice_que_un_gestor_puede_repartir_y_configurar(): void
    {
        // Nota: `can_manage` es true para todo el que llega hasta aquí. El
        // grupo exige super-admin|super-settings y ambos roles incluyen
        // helpdesk.tickets.settings, así que la distinción fina de permisos
        // no es observable en estas rutas — se publica igualmente porque el
        // JS la consulta y porque los roles podrían cambiar.
        $data = $this->overview();

        $this->assertTrue($data['can_manage']);
        $this->assertTrue($data['can_distribute']);
    }

    // ─── helpers ─────────────────────────────────────────────────────────

    /**
     * Las rutas de este controlador las añade el coordinador a
     * routes/managers.php; aquí se registran con el mismo nombre para poder
     * probar el endpoint de punta a punta.
     */
    private function registerRoutes(): void
    {
        if (Route::has('manager.helpdesk.tickets.workload.overview')) {
            return;
        }

        Route::middleware(['web', 'auth'])->prefix('panel/helpdesk')->group(function () {
            Route::get('/tickets/workload/overview', [TicketOpsWorkloadController::class, 'overview'])
                ->name('manager.helpdesk.tickets.workload.overview');
            Route::post('/tickets/workload/assignment', [TicketOpsWorkloadController::class, 'updateAssignment'])
                ->name('manager.helpdesk.tickets.workload.assignment');
        });

        app('router')->getRoutes()->refreshNameLookups();
    }

    private function forgetSettingCache(): void
    {
        foreach (self::SETTING_KEYS as $key) {
            Cache::forget('helpdesk:setting:'.$key);
        }
    }

    /**
     * Usuario que SÍ atraviesa el middleware del grupo de manager.
     *
     * Las rutas de este modal cuelgan del grupo
     * `role:super-admin|super-settings` (HelpdeskTicketsServiceProvider::
     * loadManagerRoutes). Mientras el test registraba sus propias rutas ese
     * middleware no existía y bastaba con el permiso suelto; ya integradas,
     * sin rol todo responde 403 antes de llegar al controlador.
     */
    private function manager(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('super-settings', 'web'));

        return $user;
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function userWithPermissions(array $permissions): User
    {
        $user = User::factory()->create();

        foreach ($permissions as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        return $user;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createAgent(string $firstname, string $email, array $attributes = []): User
    {
        $user = User::factory()->create(array_merge([
            'firstname' => $firstname,
            'lastname' => 'Workload',
            'email' => $email,
        ], $attributes));

        $user->assignRole('helpdesk-agent');

        return $user;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeTicket(?int $assigneeId, array $attributes = []): Ticket
    {
        return Ticket::create(array_merge([
            'subject' => 'Carga de agentes',
            'description' => 'Ticket de prueba del modal de carga.',
            'customer_id' => $this->customer->id,
            'status_id' => $this->openStatus->id,
            'priority' => 'normal',
            'source' => 'portal',
            'assignee_id' => $assigneeId,
        ], $attributes));
    }

    private function createGroupWithAgent(string $name, int $userId): int
    {
        $connection = DB::connection('helpdesk');

        $groupId = $connection->table('helpdesk_groups')->insertGetId([
            'name' => $name,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $connection->table('helpdesk_group_user')->insert([
            'group_id' => $groupId,
            'user_id' => $userId,
            'conversation_priority' => 'primary',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $groupId;
    }

    /**
     * @return array<string, mixed>
     */
    private function overview(): array
    {
        return $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.tickets.workload.overview'))
            ->assertOk()
            ->json();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function agentRow(array $data, int $agentId): array
    {
        $row = collect($data['agents'])->firstWhere('id', $agentId);

        $this->assertNotNull($row, "El agente #{$agentId} debería aparecer en el resumen de carga.");

        return $row;
    }

    private function helpdeskConnectionAvailable(): bool
    {
        try {
            DB::connection('helpdesk')->getPdo();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
