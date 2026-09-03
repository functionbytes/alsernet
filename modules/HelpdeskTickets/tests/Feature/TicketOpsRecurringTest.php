<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Route;
use Modules\HelpdeskTickets\Http\Controllers\Managers\TicketOpsRecurringController;
use Modules\HelpdeskTickets\Models\RecurringTicket;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Modal 30 "Tickets recurrentes" del riel de operación de /tickets.
 *
 * El modal solo listaba las recurrencias activas y remataba con un enlace a
 * Ajustes; estos endpoints le permiten crear, editar y pausar sin salir del
 * listado.
 */
class TicketOpsRecurringTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registrarRutas();

        $rol = Role::firstOrCreate(['name' => 'super-settings', 'guard_name' => 'web']);
        $this->manager = User::factory()->create();
        $this->manager->assignRole($rol);
    }

    /**
     * Las rutas todavía no están en routes/managers.php (las integra el
     * coordinador). Se registran aquí con el MISMO prefijo, middleware, URI y
     * nombres propuestos en el informe, y solo si aún no existen: cuando se
     * añadan al módulo, este bloque deja de hacer nada y el test sigue
     * corriendo contra las rutas reales.
     *
     * La URI cuelga de /tickets/ops/ y no de /tickets/recurring por el
     * catch-all GET /tickets/{ticket}: con dos segmentos, /tickets/recurring
     * lo capturaba él y devolvía 404 (fallo real de este mismo test antes de
     * cambiarla). Así las rutas funcionan se peguen donde se peguen.
     */
    private function registrarRutas(): void
    {
        if (Route::has('manager.helpdesk.tickets.recurring.index')) {
            return;
        }

        Route::middleware(['web', 'auth', 'role:super-admin|super-settings'])
            ->prefix('panel/helpdesk')
            ->group(function () {
                Route::get('/tickets/ops/recurring', [TicketOpsRecurringController::class, 'index'])
                    ->name('manager.helpdesk.tickets.recurring.index');
                Route::post('/tickets/ops/recurring', [TicketOpsRecurringController::class, 'store'])
                    ->name('manager.helpdesk.tickets.recurring.store');
                Route::post('/tickets/ops/recurring/{recurringTicket}', [TicketOpsRecurringController::class, 'update'])
                    ->name('manager.helpdesk.tickets.recurring.update');
                Route::post('/tickets/ops/recurring/{recurringTicket}/toggle', [TicketOpsRecurringController::class, 'toggle'])
                    ->name('manager.helpdesk.tickets.recurring.toggle');
            });

        Route::getRoutes()->refreshNameLookups();
    }

    private function crearRecurrencia(array $atributos = []): RecurringTicket
    {
        return RecurringTicket::create(array_merge([
            'name' => 'Control diario de pedidos sin salir '.uniqid(),
            'subject' => 'Revisar pedidos sin salir',
            'frequency' => 'daily',
            'next_run_at' => now()->addHours(12),
            'is_active' => true,
            'tickets_created' => 0,
        ], $atributos));
    }

    /** Payload mínimo válido del formulario del modal. */
    private function formulario(array $atributos = []): array
    {
        return array_merge([
            'name' => 'Revisión mensual de documentación '.uniqid(),
            'subject' => 'Revisar documentación fiscal',
            'frequency' => 'monthly',
        ], $atributos);
    }

    // ── Listado ───────────────────────────────────────────────

    public function test_lista_tambien_las_recurrencias_pausadas(): void
    {
        $activa = $this->crearRecurrencia();
        $pausada = $this->crearRecurrencia(['is_active' => false]);

        $ids = array_column(
            $this->actingAs($this->manager)
                ->getJson(route('manager.helpdesk.tickets.recurring.index'))
                ->assertOk()
                ->json('data'),
            'id'
        );

        // settingsSnapshot solo devolvía las activas: una recurrencia pausada
        // desaparecía del modal y no había forma de reanudarla desde ahí.
        $this->assertContains($activa->id, $ids);
        $this->assertContains($pausada->id, $ids);
    }

    public function test_cada_recurrencia_trae_su_proxima_ejecucion_y_cuantos_tickets_ha_creado(): void
    {
        $recurrencia = $this->crearRecurrencia(['tickets_created' => 7]);

        $fila = collect(
            $this->actingAs($this->manager)
                ->getJson(route('manager.helpdesk.tickets.recurring.index'))
                ->assertOk()
                ->json('data')
        )->firstWhere('id', $recurrencia->id);

        $this->assertSame(7, $fila['tickets_created']);
        $this->assertSame('Diaria', $fila['frequency_label']);
        $this->assertNotNull($fila['next_run_at']);
        $this->assertNotNull($fila['next_run_at_human']);
        $this->assertTrue($fila['is_active']);
    }

    public function test_devuelve_los_catalogos_que_rellenan_el_formulario(): void
    {
        $catalogos = $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.tickets.recurring.index'))
            ->assertOk()
            ->json('catalogs');

        $this->assertArrayHasKey('categories', $catalogos);
        $this->assertArrayHasKey('priorities', $catalogos);
        $this->assertArrayHasKey('agents', $catalogos);
        $this->assertSame(
            ['daily', 'weekly', 'monthly', 'custom'],
            array_column($catalogos['frequencies'], 'value')
        );
    }

    // ── Alta ──────────────────────────────────────────────────

    public function test_crea_la_recurrencia_ya_programada_aunque_no_se_indique_fecha(): void
    {
        $item = $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.recurring.store'), $this->formulario(['frequency' => 'weekly']))
            ->assertCreated()
            ->json('item');

        $recurrencia = RecurringTicket::findOrFail($item['id']);

        // Sin next_run_at el job no la ve nunca (scopeDueToRun filtra por
        // whereNotNull), así que nacería muerta.
        $this->assertNotNull($recurrencia->next_run_at);
        $this->assertTrue($recurrencia->next_run_at->isFuture());
        $this->assertTrue($recurrencia->is_active);
        $this->assertSame(0, $recurrencia->tickets_created);
    }

    public function test_respeta_la_primera_ejecucion_indicada(): void
    {
        $cuando = now()->addDays(3)->startOfHour();

        $item = $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.recurring.store'), $this->formulario([
                'next_run_at' => $cuando->format('Y-m-d\TH:i'),
            ]))
            ->assertCreated()
            ->json('item');

        $this->assertSame(
            $cuando->format('Y-m-d H:i'),
            RecurringTicket::findOrFail($item['id'])->next_run_at->format('Y-m-d H:i')
        );
    }

    public function test_no_admite_frecuencias_que_el_modelo_no_sabe_calcular(): void
    {
        // El mockup pide "trimestral" y la enum de la tabla no la tiene; y
        // 'custom' exige una expresión cron que el modal no pide.
        foreach (['quarterly', 'custom'] as $frecuencia) {
            $this->actingAs($this->manager)
                ->postJson(route('manager.helpdesk.tickets.recurring.store'), $this->formulario(['frequency' => $frecuencia]))
                ->assertStatus(422)
                ->assertJsonValidationErrors('frequency');
        }
    }

    public function test_exige_nombre_y_asunto(): void
    {
        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.recurring.store'), ['frequency' => 'daily'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'subject']);
    }

    public function test_un_usuario_sin_permisos_no_puede_crear_recurrencias(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson(route('manager.helpdesk.tickets.recurring.store'), $this->formulario())
            ->assertForbidden();
    }

    // ── Edición ───────────────────────────────────────────────

    public function test_guarda_los_cambios_de_una_recurrencia_existente(): void
    {
        $recurrencia = $this->crearRecurrencia(['tickets_created' => 4]);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.recurring.update', $recurrencia), [
                'name' => 'Control diario revisado',
                'subject' => 'Pedidos sin salir (revisado)',
                'description' => 'Repasar los pedidos parados más de 48 h.',
                'frequency' => 'daily',
            ])
            ->assertOk()
            ->assertJsonPath('item.name', 'Control diario revisado');

        $recurrencia->refresh();

        $this->assertSame('Pedidos sin salir (revisado)', $recurrencia->subject);
        // El contador es historial: guardar no lo toca.
        $this->assertSame(4, $recurrencia->tickets_created);
    }

    public function test_guardar_no_reanuda_una_recurrencia_pausada(): void
    {
        $recurrencia = $this->crearRecurrencia(['is_active' => false]);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.recurring.update', $recurrencia), [
                'name' => 'Sigue pausada',
                'subject' => 'Sigue pausada',
                'frequency' => 'daily',
            ])
            ->assertOk();

        // Pausar y reanudar es el botón aparte, no un efecto de "Guardar".
        $this->assertFalse($recurrencia->refresh()->is_active);
    }

    public function test_al_cambiar_la_frecuencia_reprograma_la_proxima_ejecucion(): void
    {
        $recurrencia = $this->crearRecurrencia(['next_run_at' => now()->addHours(2)]);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.recurring.update', $recurrencia), [
                'name' => $recurrencia->name,
                'subject' => $recurrencia->subject,
                'frequency' => 'monthly',
            ])
            ->assertOk();

        $this->assertTrue($recurrencia->refresh()->next_run_at->greaterThan(now()->addWeeks(3)));
    }

    public function test_una_recurrencia_con_cron_conserva_su_expresion_al_guardarla(): void
    {
        $recurrencia = $this->crearRecurrencia(['frequency' => 'custom', 'cron_expression' => '0 9 * * 1']);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.recurring.update', $recurrencia), [
                'name' => 'Cron intacto',
                'subject' => 'Cron intacto',
                'frequency' => 'custom',
            ])
            ->assertOk();

        $recurrencia->refresh();

        // El modal no edita crons, pero tampoco puede romperlos: solo la
        // pantalla completa de Ajustes toca cron_expression.
        $this->assertSame('custom', $recurrencia->frequency);
        $this->assertSame('0 9 * * 1', $recurrencia->cron_expression);
        $this->assertSame('Cron intacto', $recurrencia->name);
    }

    // ── Pausar / reanudar ─────────────────────────────────────

    public function test_pausar_desactiva_la_recurrencia_sin_perder_el_contador(): void
    {
        $recurrencia = $this->crearRecurrencia(['tickets_created' => 12]);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.recurring.toggle', $recurrencia))
            ->assertOk()
            ->assertJsonPath('item.is_active', false);

        $recurrencia->refresh();

        $this->assertFalse($recurrencia->is_active);
        $this->assertSame(12, $recurrencia->tickets_created);
    }

    public function test_reanudar_reprograma_una_proxima_ejecucion_que_se_quedo_en_el_pasado(): void
    {
        $recurrencia = $this->crearRecurrencia([
            'is_active' => false,
            'next_run_at' => now()->subMonth(),
        ]);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.recurring.toggle', $recurrencia))
            ->assertOk()
            ->assertJsonPath('item.is_active', true);

        // Si no se reprogramara, el job crearía un ticket en cuanto arrancase.
        $this->assertTrue($recurrencia->refresh()->next_run_at->isFuture());
    }

    public function test_reanudar_respeta_una_proxima_ejecucion_futura(): void
    {
        $cuando = now()->addDays(5)->startOfHour();
        $recurrencia = $this->crearRecurrencia(['is_active' => false, 'next_run_at' => $cuando]);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.recurring.toggle', $recurrencia))
            ->assertOk();

        $this->assertSame(
            $cuando->format('Y-m-d H:i'),
            $recurrencia->refresh()->next_run_at->format('Y-m-d H:i')
        );
    }
}
