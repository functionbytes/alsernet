<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Modules\HelpdeskTickets\Http\Controllers\Managers\TicketOpsMailboxesController;
use Modules\HelpdeskTickets\Services\TicketEmailChannelsRepository;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Modal "Buzones" del riel de operación (TicketOpsMailboxesController).
 *
 * SEGURIDAD DE DATOS — este módulo toca canales de correo REALES:
 *  - cada test crea SUS PROPIOS canales y solo afirma sobre ellos; nunca se
 *    vacía ni se reescribe el blob `incoming_email` para "empezar limpio"
 *    (ese patrón es el que dejó la configuración real a cero en incidentes
 *    anteriores, ver la guarda de Tests\TestCase);
 *  - las tres conexiones están declaradas en $connectionsToTransact, así que
 *    lo escrito se revierte;
 *  - tearDown() invalida la caché del setting y la de salud de los canales
 *    creados: Setting::get() cachea 10 minutos POR FUERA de la transacción y
 *    dejaría a la aplicación real leyendo un blob que ya no existe en BD;
 *  - ninguna prueba abre una conexión IMAP real: los dos casos de "probar
 *    conexión" que se ejercitan son los que cortan ANTES del fsockopen.
 */
class TicketOpsMailboxesTest extends TestCase
{
    use DatabaseTransactions;

    // 'mysql' es la conexión real de Setting/settings — sin ella el blob
    // `incoming_email` que estos tests escriben NO se revierte.
    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private User $manager;

    private User $agent;

    /** @var array<int, string> Ids de los canales creados por el test en curso. */
    private array $createdChannelIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerRoutes();

        Permission::firstOrCreate(['name' => 'helpdesk.tickets.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'helpdesk.tickets.settings', 'guard_name' => 'web']);

        // Permisos directos al usuario, sin tocar ningún rol: los roles
        // reales ('super-settings', 'super-admin') existen en esta base y
        // añadirles o quitarles permisos afectaría a gente de verdad si algo
        // impidiera el rollback.
        $this->manager = User::factory()->create();
        $this->manager->givePermissionTo('helpdesk.tickets.view', 'helpdesk.tickets.settings');

        // Agente que ve tickets pero no administra ajustes: es quien debe
        // poder abrir el modal y NO poder tocar los interruptores.
        $this->agent = User::factory()->create();
        $this->agent->givePermissionTo('helpdesk.tickets.view');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function tearDown(): void
    {
        Cache::forget('setting_incoming_email');

        foreach ($this->createdChannelIds as $id) {
            Cache::forget('incoming_email:health:'.$id);
        }

        $this->createdChannelIds = [];

        parent::tearDown();
    }

    /**
     * Rutas del modal. No viven todavía en routes/managers.php (las registra
     * el coordinador), así que se declaran aquí con los nombres exactos que
     * se van a pedir. El grupo real añade además
     * `role:super-admin|super-settings`; aquí se omite a propósito para poder
     * ejercitar el permiso `helpdesk.tickets.settings` con usuarios sin
     * ningún rol asignado — el rol es una puerta exterior más gruesa que no
     * cambia lo que hace el controlador.
     */
    private function registerRoutes(): void
    {
        Route::middleware(['web', 'auth'])->prefix('panel/helpdesk')->group(function () {
            Route::get('/tickets/ops/mailboxes', [TicketOpsMailboxesController::class, 'index'])
                ->name('manager.helpdesk.tickets.mailboxes.index');
            Route::post('/tickets/ops/mailboxes/{channel}/behavior', [TicketOpsMailboxesController::class, 'behavior'])
                ->name('manager.helpdesk.tickets.mailboxes.behavior');
            Route::post('/tickets/ops/mailboxes/{channel}/test', [TicketOpsMailboxesController::class, 'test'])
                ->name('manager.helpdesk.tickets.mailboxes.test');
        });

        // Imprescindible al registrar rutas después del arranque: el índice
        // por nombre que usa route() lo reconstruye RouteServiceProvider en
        // un callback de `booted`, que ya pasó. Sin esto, route() no
        // encuentra estas rutas aunque estén en la colección.
        Route::getRoutes()->refreshNameLookups();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function makeChannel(array $overrides = []): array
    {
        $channel = app(TicketEmailChannelsRepository::class)->create(array_merge([
            'name' => 'Buzón de prueba '.uniqid(),
            'host' => 'imap.example.test',
            'port' => 993,
            'username' => 'pruebas@example.test',
            'password' => 'secreto-de-prueba',
            'folder' => 'INBOX',
            'encryption' => 'ssl',
            'create_tickets' => true,
            'create_replies' => false,
        ], $overrides));

        $this->createdChannelIds[] = $channel['id'];

        return $channel;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function mailboxFromResponse(TestResponse $response, string $id): ?array
    {
        foreach ($response->json('mailboxes') ?? [] as $mailbox) {
            if (($mailbox['id'] ?? null) === $id) {
                return $mailbox;
            }
        }

        return null;
    }

    // ─── Listado ──────────────────────────────────────────────────────────

    public function test_el_listado_devuelve_el_buzon_con_su_estado(): void
    {
        // La salud se siembra dentro del propio canal, no con recordHealth():
        // recordHealth() escribe en caché y la caché de este entorno es un
        // Redis COMPARTIDO — cualquier otra corrida de tests hace Cache::flush()
        // en Tests\TestCase::setUp() y borraría el dato entre la escritura y la
        // petición. El repositorio ya lee la salud del propio canal cuando no
        // hay nada cacheado (compatibilidad con canales anteriores a la caché),
        // así que este camino es el mismo dato por una vía estable.
        $channel = $this->makeChannel([
            'name' => 'Soporte entrante',
            'create_replies' => true,
            'last_checked_at' => now()->toISOString(),
            'last_error' => 'Authentication failed',
        ]);

        $response = $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.tickets.mailboxes.index'));

        $response->assertOk();

        $mailbox = $this->mailboxFromResponse($response, $channel['id']);

        $this->assertNotNull($mailbox, 'El buzón creado por el test no aparece en el listado.');
        $this->assertSame('Soporte entrante', $mailbox['name']);
        $this->assertSame('imap.example.test', $mailbox['host']);
        $this->assertSame(993, $mailbox['port']);
        $this->assertTrue($mailbox['create_tickets']);
        $this->assertTrue($mailbox['create_replies']);
        $this->assertSame('Authentication failed', $mailbox['last_error']);
        $this->assertNotNull($mailbox['last_checked_at']);
    }

    public function test_el_listado_no_expone_la_contrasena_del_buzon(): void
    {
        $channel = $this->makeChannel(['password' => 'no-debe-salir-de-aqui']);

        $response = $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.tickets.mailboxes.index'));

        $response->assertOk();
        $response->assertDontSee('no-debe-salir-de-aqui');

        $mailbox = $this->mailboxFromResponse($response, $channel['id']);

        $this->assertNotNull($mailbox);
        $this->assertArrayNotHasKey('password', $mailbox);
    }

    public function test_el_listado_dice_si_el_usuario_puede_gestionar_los_buzones(): void
    {
        $this->makeChannel();

        $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.tickets.mailboxes.index'))
            ->assertOk()
            ->assertJsonPath('can_manage', true);

        // El agente sin permiso de ajustes SÍ ve la lista (es la misma
        // información que ya servía settings-snapshot), pero el modal debe
        // pintarle los interruptores desactivados.
        $this->actingAs($this->agent)
            ->getJson(route('manager.helpdesk.tickets.mailboxes.index'))
            ->assertOk()
            ->assertJsonPath('can_manage', false);
    }

    // ─── Interruptores de comportamiento ──────────────────────────────────

    public function test_guarda_los_dos_interruptores_del_buzon(): void
    {
        $channel = $this->makeChannel(['create_tickets' => true, 'create_replies' => false]);

        $response = $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.mailboxes.behavior', ['channel' => $channel['id']]), [
                'create_tickets' => 0,
                'create_replies' => 1,
            ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('mailbox.create_tickets', false);
        $response->assertJsonPath('mailbox.create_replies', true);

        $stored = app(TicketEmailChannelsRepository::class)->find($channel['id']);

        $this->assertFalse($stored['create_tickets']);
        $this->assertTrue($stored['create_replies']);
    }

    public function test_guardar_los_interruptores_conserva_las_credenciales_del_buzon(): void
    {
        $channel = $this->makeChannel(['password' => 'secreto-que-debe-sobrevivir']);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.mailboxes.behavior', ['channel' => $channel['id']]), [
                'create_tickets' => 1,
                'create_replies' => 1,
            ])
            ->assertOk();

        $stored = app(TicketEmailChannelsRepository::class)->find($channel['id']);

        $this->assertSame('secreto-que-debe-sobrevivir', $stored['password']);
        $this->assertSame('imap.example.test', $stored['host']);
        $this->assertSame('pruebas@example.test', $stored['username']);
        $this->assertSame(993, $stored['port']);
    }

    public function test_guardar_un_buzon_no_toca_los_demas(): void
    {
        $otro = $this->makeChannel(['name' => 'Buzón intacto', 'create_tickets' => true, 'create_replies' => true]);
        $objetivo = $this->makeChannel(['name' => 'Buzón a cambiar']);

        $antes = count(app(TicketEmailChannelsRepository::class)->all());

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.mailboxes.behavior', ['channel' => $objetivo['id']]), [
                'create_tickets' => 0,
                'create_replies' => 0,
            ])
            ->assertOk();

        $intacto = app(TicketEmailChannelsRepository::class)->find($otro['id']);

        $this->assertTrue($intacto['create_tickets']);
        $this->assertTrue($intacto['create_replies']);
        $this->assertSame('Buzón intacto', $intacto['name']);

        // Ningún canal desaparece al guardar: el repositorio reescribe el
        // blob entero en cada update y una regresión ahí se llevaría por
        // delante buzones reales.
        $this->assertCount($antes, app(TicketEmailChannelsRepository::class)->all());
    }

    public function test_avisa_cuando_el_buzon_se_queda_sin_leer(): void
    {
        $channel = $this->makeChannel();

        // Con los dos interruptores apagados FetchTicketEmailsJob se salta el
        // buzón entero; el mensaje debe decirlo, no limitarse a "guardado".
        $response = $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.mailboxes.behavior', ['channel' => $channel['id']]), [
                'create_tickets' => 0,
                'create_replies' => 0,
            ]);

        $response->assertOk();
        $this->assertStringContainsString('Ya no se leerá', $response->json('message'));
    }

    public function test_rechaza_guardar_sin_indicar_los_dos_interruptores(): void
    {
        $channel = $this->makeChannel();

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.mailboxes.behavior', ['channel' => $channel['id']]), [
                'create_tickets' => 1,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('create_replies');
    }

    public function test_un_agente_sin_permiso_de_ajustes_no_puede_cambiar_los_interruptores(): void
    {
        $channel = $this->makeChannel(['create_tickets' => true, 'create_replies' => false]);

        $this->actingAs($this->agent)
            ->postJson(route('manager.helpdesk.tickets.mailboxes.behavior', ['channel' => $channel['id']]), [
                'create_tickets' => 0,
                'create_replies' => 0,
            ])
            ->assertStatus(403);

        $stored = app(TicketEmailChannelsRepository::class)->find($channel['id']);

        $this->assertTrue($stored['create_tickets']);
    }

    public function test_devuelve_404_al_cambiar_un_buzon_que_no_existe(): void
    {
        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.mailboxes.behavior', ['channel' => 'imapchannel_inexistente']), [
                'create_tickets' => 1,
                'create_replies' => 1,
            ])
            ->assertStatus(404);
    }

    // ─── Prueba de conexión ───────────────────────────────────────────────

    public function test_la_prueba_de_conexion_rechaza_un_puerto_que_no_es_de_correo(): void
    {
        // Se corta antes de abrir ningún socket: el buzón guardado apunta a
        // un puerto que no es de correo, así que el botón no puede servir
        // para sondear la red interna.
        $channel = $this->makeChannel(['port' => 8080]);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.mailboxes.test', ['channel' => $channel['id']]))
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_la_prueba_de_conexion_rechaza_un_servidor_de_loopback(): void
    {
        // 127.0.0.1 lo bloquea TicketEmailChannelUrlGuard antes del
        // fsockopen: sin esto la prueba llegaría a servicios del propio
        // contenedor.
        $channel = $this->makeChannel(['host' => '127.0.0.1']);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.mailboxes.test', ['channel' => $channel['id']]))
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_la_prueba_de_conexion_devuelve_404_si_el_buzon_no_existe(): void
    {
        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.mailboxes.test', ['channel' => 'imapchannel_inexistente']))
            ->assertStatus(404);
    }

    public function test_un_agente_sin_permiso_de_ajustes_no_puede_lanzar_la_prueba_de_conexion(): void
    {
        $channel = $this->makeChannel();

        $this->actingAs($this->agent)
            ->postJson(route('manager.helpdesk.tickets.mailboxes.test', ['channel' => $channel['id']]))
            ->assertStatus(403);
    }
}
