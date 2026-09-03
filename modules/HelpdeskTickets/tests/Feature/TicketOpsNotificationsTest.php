<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Route;
use Modules\HelpdeskTickets\Http\Controllers\Managers\TicketOpsNotificationsController;
use Modules\Notification\Models\NotificationPreference;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Preferencias de aviso del modal "Avisos" del riel de operación.
 *
 * El sistema de preferencias (NotificationPreference, tabla
 * notification_settings) ya se consultaba en las via() de las notificaciones
 * de tickets, pero no había ninguna pantalla donde el agente pudiera
 * cambiarlas. Estos endpoints son esa pantalla.
 */
class TicketOpsNotificationsTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private User $agente;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registrarRutasSiHacenFalta();

        $role = Role::firstOrCreate(['name' => 'super-settings', 'guard_name' => 'web']);
        $this->agente = User::factory()->create();
        $this->agente->assignRole($role);
    }

    /**
     * Las rutas definitivas las añade el coordinador a routes/managers.php
     * (ese archivo no se toca desde aquí). Mientras no estén, se registran con
     * el mismo nombre para que el test ejercite el controlador de verdad; en
     * cuanto existan, este método no hace nada y se prueba la ruta real.
     */
    private function registrarRutasSiHacenFalta(): void
    {
        $router = app('router');

        if ($router->getRoutes()->hasNamedRoute('manager.helpdesk.tickets.notification-preferences')) {
            return;
        }

        // Cuelgan de /tickets/ops/… y no de /tickets/… a propósito: la ruta
        // GET /tickets/{ticket} (managers.php:188) se traga cualquier GET de un
        // solo segmento e intenta resolver "notification-preferences" como un
        // ticket, devolviendo 404. Con dos segmentos no hay colisión posible y
        // el coordinador puede añadirlas en cualquier punto del archivo.
        Route::middleware(['web', 'auth'])->prefix('panel/helpdesk')->group(function () {
            Route::get('/tickets/ops/notification-preferences', [TicketOpsNotificationsController::class, 'index'])
                ->name('manager.helpdesk.tickets.notification-preferences');
            Route::post('/tickets/ops/notification-preferences', [TicketOpsNotificationsController::class, 'update'])
                ->name('manager.helpdesk.tickets.notification-preferences.update');
        });

        $router->getRoutes()->refreshNameLookups();
    }

    private function leerCatalogo(): array
    {
        return $this->actingAs($this->agente)
            ->getJson(route('manager.helpdesk.tickets.notification-preferences'))
            ->assertOk()
            ->json();
    }

    private function guardar(array $preferences)
    {
        return $this->actingAs($this->agente)
            ->postJson(route('manager.helpdesk.tickets.notification-preferences.update'), [
                'preferences' => $preferences,
            ]);
    }

    public function test_el_catalogo_solo_lista_eventos_que_consultan_la_preferencia(): void
    {
        $catalogo = $this->leerCatalogo();

        $claves = array_column($catalogo['events'], 'key');

        // Los siete eventos cuyas via() llaman a NotificationPreference::isEnabled().
        $this->assertEqualsCanonicalizing([
            'ticket.assigned',
            'ticket.status_changed',
            'ticket.sla.warning',
            'ticket.sla.breached',
            'ticket.mention',
            'ticket.created',
            'ticket.automation',
        ], $claves);
    }

    public function test_solo_ofrece_los_canales_que_cada_evento_puede_apagar(): void
    {
        $catalogo = $this->leerCatalogo();
        $porClave = collect($catalogo['events'])->keyBy('key');

        // TicketAssigned pregunta por los dos canales.
        $this->assertEqualsCanonicalizing(
            ['in_app', 'push'],
            array_column($porClave['ticket.assigned']['channels'], 'key')
        );

        // TicketMentionNotification fija 'database' antes de preguntar: el aviso
        // del panel llega siempre, así que no se ofrece esa casilla.
        $this->assertSame(
            ['push'],
            array_column($porClave['ticket.mention']['channels'], 'key')
        );

        // Mismo caso en TicketCreated.
        $this->assertSame(
            ['push'],
            array_column($porClave['ticket.created']['channels'], 'key')
        );
    }

    public function test_sin_fila_guardada_las_casillas_salen_activadas(): void
    {
        // isEnabled() devuelve true cuando no hay preferencia: el modal tiene
        // que enseñar lo mismo que hará el sistema al notificar.
        $catalogo = $this->leerCatalogo();

        foreach ($catalogo['events'] as $evento) {
            foreach ($evento['channels'] as $canal) {
                $this->assertTrue(
                    $canal['enabled'],
                    "El canal {$canal['key']} de {$evento['key']} debería salir activado por defecto"
                );
            }
        }
    }

    public function test_guarda_la_preferencia_y_is_enabled_la_respeta(): void
    {
        $this->guardar([
            ['notification_type' => 'ticket.assigned', 'channel' => 'push', 'enabled' => false],
        ])->assertOk()->assertJsonPath('saved', 1);

        $this->assertDatabaseHas('notification_settings', [
            'user_id' => $this->agente->id,
            'notification_type' => 'ticket.assigned',
            'channel' => 'push',
            'enabled' => 0,
        ], 'mysql');

        // Lo que de verdad importa: que la via() de TicketAssigned deje de
        // añadir 'broadcast' para este usuario.
        $this->assertFalse(
            NotificationPreference::isEnabled($this->agente->id, 'push', 'ticket.assigned')
        );
        $this->assertTrue(
            NotificationPreference::isEnabled($this->agente->id, 'in_app', 'ticket.assigned')
        );
    }

    public function test_el_catalogo_refleja_lo_ya_guardado(): void
    {
        $this->guardar([
            ['notification_type' => 'ticket.sla.breached', 'channel' => 'in_app', 'enabled' => false],
        ])->assertOk();

        $catalogo = $this->leerCatalogo();
        $evento = collect($catalogo['events'])->firstWhere('key', 'ticket.sla.breached');
        $canales = collect($evento['channels'])->keyBy('key');

        $this->assertFalse($canales['in_app']['enabled']);
        $this->assertTrue($canales['push']['enabled']);
    }

    public function test_volver_a_activar_actualiza_la_fila_en_lugar_de_duplicarla(): void
    {
        $payload = fn (bool $enabled) => [
            ['notification_type' => 'ticket.mention', 'channel' => 'push', 'enabled' => $enabled],
        ];

        $this->guardar($payload(false))->assertOk();
        $this->guardar($payload(true))->assertOk();

        $filas = NotificationPreference::query()
            ->where('user_id', $this->agente->id)
            ->where('notification_type', 'ticket.mention')
            ->where('channel', 'push')
            ->count();

        $this->assertSame(1, $filas);
        $this->assertTrue(NotificationPreference::isEnabled($this->agente->id, 'push', 'ticket.mention'));
    }

    public function test_guarda_varias_casillas_en_una_sola_peticion(): void
    {
        $this->guardar([
            ['notification_type' => 'ticket.assigned', 'channel' => 'in_app', 'enabled' => false],
            ['notification_type' => 'ticket.assigned', 'channel' => 'push', 'enabled' => false],
            ['notification_type' => 'ticket.automation', 'channel' => 'push', 'enabled' => false],
        ])->assertOk()->assertJsonPath('saved', 3);

        $this->assertFalse(NotificationPreference::isEnabled($this->agente->id, 'in_app', 'ticket.assigned'));
        $this->assertFalse(NotificationPreference::isEnabled($this->agente->id, 'push', 'ticket.assigned'));
        $this->assertFalse(NotificationPreference::isEnabled($this->agente->id, 'push', 'ticket.automation'));
    }

    public function test_ignora_pares_evento_canal_que_no_tienen_casilla(): void
    {
        // 'in_app' de ticket.mention no se ofrece porque el aviso del panel es
        // incondicional: guardar esa fila dejaría una preferencia invisible en
        // el modal y por tanto irreversible desde él.
        $this->guardar([
            ['notification_type' => 'ticket.mention', 'channel' => 'in_app', 'enabled' => false],
        ])->assertOk()->assertJsonPath('saved', 0);

        $this->assertDatabaseMissing('notification_settings', [
            'user_id' => $this->agente->id,
            'notification_type' => 'ticket.mention',
            'channel' => 'in_app',
        ], 'mysql');
    }

    public function test_rechaza_un_evento_desconocido(): void
    {
        $this->guardar([
            ['notification_type' => 'ticket.inventado', 'channel' => 'push', 'enabled' => false],
        ])->assertOk()->assertJsonPath('saved', 0);

        $this->assertDatabaseMissing('notification_settings', [
            'user_id' => $this->agente->id,
            'notification_type' => 'ticket.inventado',
        ], 'mysql');
    }

    public function test_rechaza_un_canal_fuera_de_los_dos_admitidos(): void
    {
        // 'email' existe en la tabla pero ninguna via() de tickets lo consulta.
        $this->guardar([
            ['notification_type' => 'ticket.assigned', 'channel' => 'email', 'enabled' => false],
        ])->assertStatus(422)->assertJsonValidationErrors('preferences.0.channel');
    }

    public function test_exige_al_menos_una_preferencia(): void
    {
        $this->guardar([])->assertStatus(422)->assertJsonValidationErrors('preferences');
    }

    public function test_un_agente_no_puede_cambiar_las_preferencias_de_otro(): void
    {
        $otro = User::factory()->create();

        // Aunque cuele un user_id en el cuerpo, el controlador escribe siempre
        // con el id del usuario autenticado.
        $this->actingAs($this->agente)
            ->postJson(route('manager.helpdesk.tickets.notification-preferences.update'), [
                'user_id' => $otro->id,
                'preferences' => [
                    ['notification_type' => 'ticket.assigned', 'channel' => 'push', 'enabled' => false],
                ],
            ])->assertOk();

        $this->assertDatabaseMissing('notification_settings', [
            'user_id' => $otro->id,
            'notification_type' => 'ticket.assigned',
        ], 'mysql');

        $this->assertTrue(NotificationPreference::isEnabled($otro->id, 'push', 'ticket.assigned'));
    }

    public function test_un_invitado_no_llega_a_los_endpoints(): void
    {
        $this->getJson(route('manager.helpdesk.tickets.notification-preferences'))->assertUnauthorized();
    }

    public function test_expone_los_avisos_que_no_se_pueden_desactivar(): void
    {
        // El mockup pedía "el cliente responda a un correo que envié"; ese aviso
        // existe (TicketWatcherActivityNotification) pero su via() no consulta
        // la preferencia. Se enseña como texto, sin interruptor.
        $catalogo = $this->leerCatalogo();

        $this->assertNotEmpty($catalogo['always_on']);
        $this->assertIsString($catalogo['always_on'][0]);
    }
}
