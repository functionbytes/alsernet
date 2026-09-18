<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Modules\HelpdeskTickets\Http\Controllers\Managers\Settings\TicketEmailChannelsController;
use Modules\HelpdeskTickets\Services\TicketEmailChannelsRepository;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TicketEmailChannelsControllerTest extends TestCase
{
    use DatabaseTransactions;

    // 'mysql' es la conexión real de Setting/settings — ver el comentario en
    // FetchTicketEmailsJobTest::$connectionsToTransact para el detalle del
    // incidente real que esto evita.
    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private User $manager;

    private User $unauthorized;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        // Ver TicketEmailChannelsRepositoryTest: Setting::setEncrypted()
        // cachea fuera del rollback transaccional, así que cada test arranca
        // desde un blob vacío explícito en vez de asumir DB limpia.
        DB::table('settings')->updateOrInsert(
            ['key' => 'incoming_email'],
            ['value' => json_encode(['imap' => ['connections' => []]])]
        );
        Cache::forget('setting_incoming_email');

        Permission::firstOrCreate(['name' => 'helpdesk.tickets.settings', 'guard_name' => 'web']);
        $role = Role::firstOrCreate(['name' => 'super-settings', 'guard_name' => 'web']);
        $role->givePermissionTo('helpdesk.tickets.settings');

        $this->manager = User::factory()->create();
        $this->manager->assignRole($role);

        $this->unauthorized = User::factory()->create();
    }

    public function test_index_renders_for_authorized_user(): void
    {
        app(TicketEmailChannelsRepository::class)->create([
            'name' => 'Soporte principal',
            'host' => 'imap.example.com',
            'port' => 993,
            'username' => 'soporte@example.com',
            'password' => 'secret',
            'create_tickets' => true,
        ]);

        $response = $this->actingAs($this->manager)
            ->get(route('manager.helpdesk.settings.email-channels.index'));

        $response->assertOk();
        $response->assertSee('Soporte principal');
        $response->assertSee('Sin sincronizar');
    }

    // ─── Prueba de conexion ───────────────────────────────────────────────────

    public function test_imap_test_endpoint_does_not_require_credentials(): void
    {
        // La prueba es un fsockopen: comprueba que el servidor acepta la
        // conexion, no que el usuario y la contrasena sean validos. Exigirlos
        // dejaba el boton inservible al editar, donde el campo de contrasena se
        // sirve vacio a proposito.
        $response = $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.settings.email-channels.test'), [
                'host' => 'imap.example.com',
                'port' => 993,
            ]);

        // Sin aserción sobre el código: la petición pasa la validación y llega
        // al guard SSRF, que decide según resuelva el host — eso depende del
        // DNS de la máquina y no es lo que se está probando aquí. Lo que
        // importa es que la validación ya no reclama credenciales.
        $response->assertJsonMissingValidationErrors(['username', 'password']);
    }

    public function test_imap_test_endpoint_still_requires_host_and_port(): void
    {
        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.settings.email-channels.test'), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['host', 'port']);
    }

    public function test_imap_test_endpoint_rejects_a_disallowed_host(): void
    {
        // El guard SSRF sigue en pie tras quitar las credenciales del validate.
        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.settings.email-channels.test'), [
                'host' => '127.0.0.1',
                'port' => 993,
            ])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_edit_shows_the_connection_test_buttons_in_the_status_card(): void
    {
        $channel = $this->makeChannel(['name' => 'Canal editable']);

        $html = $this->actingAs($this->manager)
            ->get(route('manager.helpdesk.settings.email-channels.edit', $channel['id']))
            ->assertOk()
            ->getContent();

        // Los botones existen y estan DESPUES de "Estado del canal", es decir,
        // dentro de la tarjeta lateral de acciones y no entre los campos.
        $this->assertStringContainsString('btn-test-channel', $html);
        $this->assertStringContainsString('btn-test-smtp-channel', $html);
        $this->assertGreaterThan(
            strpos($html, 'Estado del canal'),
            strpos($html, 'btn-test-channel'),
            'Los botones de prueba deben quedar en la tarjeta "Estado del canal".',
        );
    }

    public function test_create_keeps_the_test_buttons_in_the_form(): void
    {
        // Al crear no hay tarjeta de estado todavia, asi que siguen junto a los
        // campos que prueban.
        $html = $this->actingAs($this->manager)
            ->get(route('manager.helpdesk.settings.email-channels.create'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('btn-test-channel', $html);
        $this->assertStringContainsString('btn-test-smtp-channel', $html);
        $this->assertStringNotContainsString('Estado del canal', $html);
    }

    // ─── Buscador, paginación y acciones masivas ──────────────────────────────

    public function test_index_filters_channels_by_search(): void
    {
        $this->makeChannel(['name' => 'Soporte facturacion', 'username' => 'facturas@example.com']);
        $this->makeChannel(['name' => 'Soporte tecnico', 'username' => 'tecnico@example.com']);

        $response = $this->actingAs($this->manager)
            ->get(route('manager.helpdesk.settings.email-channels.index', ['search' => 'facturacion']));

        $response->assertOk();
        $response->assertSee('Soporte facturacion');
        $response->assertDontSee('Soporte tecnico');
    }

    public function test_search_matches_the_mailbox_user_and_the_host(): void
    {
        $this->makeChannel(['name' => 'Canal A', 'username' => 'ventas@example.com', 'host' => 'imap.uno.com']);
        $this->makeChannel(['name' => 'Canal B', 'username' => 'otro@example.com', 'host' => 'imap.dos.com']);

        $this->actingAs($this->manager)
            ->get(route('manager.helpdesk.settings.email-channels.index', ['search' => 'ventas@']))
            ->assertOk()
            ->assertSee('Canal A')
            ->assertDontSee('Canal B');

        $this->actingAs($this->manager)
            ->get(route('manager.helpdesk.settings.email-channels.index', ['search' => 'imap.dos']))
            ->assertOk()
            ->assertSee('Canal B')
            ->assertDontSee('Canal A');
    }

    public function test_search_without_results_shows_the_empty_state(): void
    {
        $this->makeChannel(['name' => 'Soporte principal']);

        $this->actingAs($this->manager)
            ->get(route('manager.helpdesk.settings.email-channels.index', ['search' => 'no-existe-nada']))
            ->assertOk()
            ->assertSee('No se encontraron resultados')
            ->assertDontSee('Soporte principal');
    }

    public function test_index_paginates_and_second_page_shows_the_rest(): void
    {
        // PER_PAGE es 15: con 17 canales la primera página trae 15 y la segunda 2.
        for ($i = 1; $i <= 17; $i++) {
            $this->makeChannel(['name' => 'Canal '.str_pad((string) $i, 2, '0', STR_PAD_LEFT)]);
        }

        $first = $this->actingAs($this->manager)
            ->get(route('manager.helpdesk.settings.email-channels.index'));

        $first->assertOk();
        $first->assertSee('Canal 01');
        $first->assertSee('Canal 15');
        $first->assertDontSee('Canal 16');
        // El pie de paginación solo aparece cuando hay más de una página.
        $first->assertSee('de 17');

        $second = $this->actingAs($this->manager)
            ->get(route('manager.helpdesk.settings.email-channels.index', ['page' => 2]));

        $second->assertOk();
        $second->assertSee('Canal 16');
        $second->assertSee('Canal 17');
        $second->assertDontSee('Canal 01');
    }

    public function test_stats_count_every_channel_not_just_the_current_page(): void
    {
        for ($i = 1; $i <= 17; $i++) {
            $this->makeChannel(['name' => 'Canal '.$i, 'create_tickets' => true]);
        }

        $response = $this->actingAs($this->manager)
            ->get(route('manager.helpdesk.settings.email-channels.index'));

        // 17, no 15: las cifras son del total configurado.
        $response->assertOk();
        $response->assertSeeInOrder(['Total', '17']);
    }

    public function test_bulk_action_deactivates_ticket_creation(): void
    {
        $a = $this->makeChannel(['name' => 'Canal A', 'create_tickets' => true]);
        $b = $this->makeChannel(['name' => 'Canal B', 'create_tickets' => true]);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.settings.email-channels.bulk-action'), [
                'action' => 'deactivate',
                'ids' => [$a['id']],
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $repo = app(TicketEmailChannelsRepository::class);
        $this->assertFalse((bool) $repo->find($a['id'])['create_tickets']);
        $this->assertTrue((bool) $repo->find($b['id'])['create_tickets'], 'No debe tocar los canales no seleccionados.');
    }

    public function test_bulk_action_activates_ticket_creation(): void
    {
        $channel = $this->makeChannel(['create_tickets' => false]);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.settings.email-channels.bulk-action'), [
                'action' => 'activate',
                'ids' => [$channel['id']],
            ])
            ->assertOk();

        $this->assertTrue((bool) app(TicketEmailChannelsRepository::class)->find($channel['id'])['create_tickets']);
    }

    public function test_bulk_action_deletes_the_selected_channels(): void
    {
        $a = $this->makeChannel(['name' => 'Canal A']);
        $b = $this->makeChannel(['name' => 'Canal B']);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.settings.email-channels.bulk-action'), [
                'action' => 'delete',
                'ids' => [$a['id'], $b['id']],
            ])
            ->assertOk();

        $repo = app(TicketEmailChannelsRepository::class);
        $this->assertNull($repo->find($a['id']));
        $this->assertNull($repo->find($b['id']));
    }

    public function test_bulk_action_ignores_ids_that_no_longer_exist(): void
    {
        $channel = $this->makeChannel();

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.settings.email-channels.bulk-action'), [
                'action' => 'delete',
                'ids' => [$channel['id'], 'imapchannel_inventado'],
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertNull(app(TicketEmailChannelsRepository::class)->find($channel['id']));
    }

    public function test_bulk_action_rejects_an_unknown_action(): void
    {
        $channel = $this->makeChannel();

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.settings.email-channels.bulk-action'), [
                'action' => 'formatear-el-disco',
                'ids' => [$channel['id']],
            ])
            ->assertStatus(422);
    }

    public function test_bulk_action_requires_at_least_one_id(): void
    {
        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.settings.email-channels.bulk-action'), [
                'action' => 'delete',
                'ids' => [],
            ])
            ->assertStatus(422);
    }

    public function test_bulk_action_forbidden_for_unauthorized_user(): void
    {
        $channel = $this->makeChannel();

        $this->actingAs($this->unauthorized)
            ->postJson(route('manager.helpdesk.settings.email-channels.bulk-action'), [
                'action' => 'delete',
                'ids' => [$channel['id']],
            ])
            ->assertForbidden();

        $this->assertNotNull(app(TicketEmailChannelsRepository::class)->find($channel['id']));
    }

    public function test_index_does_not_show_a_relative_timestamp_for_the_last_check(): void
    {
        // El "hace N horas" bajo el estado se retiró: se leía como si el error
        // acabara de ocurrir cuando solo decía cuándo se intentó por última vez.
        $channel = $this->makeChannel(['name' => 'Canal con error']);
        app(TicketEmailChannelsRepository::class)->recordHealth($channel['id'], false, 'connection failed');

        $response = $this->actingAs($this->manager)
            ->get(route('manager.helpdesk.settings.email-channels.index'));

        $response->assertOk();
        $response->assertSee('Canal con error');
        $response->assertSee('Error');
        $this->assertDoesNotMatchRegularExpression('/hace \d+ (segundo|minuto|hora|d[ií]a)/u', $response->getContent());
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function makeChannel(array $overrides = []): array
    {
        return app(TicketEmailChannelsRepository::class)->create(array_merge([
            'name' => 'Canal de prueba',
            'host' => 'imap.example.com',
            'port' => 993,
            'username' => 'buzon@example.com',
            'password' => 'secret',
            'create_tickets' => true,
        ], $overrides));
    }

    public function test_create_renders_the_full_page_form(): void
    {
        $response = $this->actingAs($this->manager)
            ->get(route('manager.helpdesk.settings.email-channels.create'));

        $response->assertOk();
        $response->assertSee('Nuevo canal de correo');
        $response->assertSee('Guardar canal');
    }

    public function test_edit_renders_the_channel_values(): void
    {
        $channel = app(TicketEmailChannelsRepository::class)->create([
            'name' => 'Soporte principal',
            'host' => 'imap.example.com',
            'port' => 993,
            'username' => 'soporte@example.com',
            'password' => 'secret',
        ]);

        $response = $this->actingAs($this->manager)
            ->get(route('manager.helpdesk.settings.email-channels.edit', $channel['id']));

        $response->assertOk();
        $response->assertSee('Soporte principal');
        $response->assertSee('imap.example.com');
        $response->assertSee('soporte@example.com');
        // La contrasena guardada nunca se vuelca en el formulario.
        $response->assertDontSee('secret');
    }

    public function test_edit_returns_404_for_unknown_channel(): void
    {
        $response = $this->actingAs($this->manager)
            ->get(route('manager.helpdesk.settings.email-channels.edit', 'does-not-exist'));

        $response->assertNotFound();
    }

    public function test_create_forbidden_for_unauthorized_user(): void
    {
        $response = $this->actingAs($this->unauthorized)
            ->get(route('manager.helpdesk.settings.email-channels.create'));

        $response->assertForbidden();
    }

    public function test_index_forbidden_for_unauthorized_user(): void
    {
        $response = $this->actingAs($this->unauthorized)
            ->get(route('manager.helpdesk.settings.email-channels.index'));

        $response->assertForbidden();
    }

    // NOTA: no se agrega un test HTTP para sync()/store()/update()/destroy()
    // (POST/PUT/DELETE) — este entorno de test tiene un 419 CSRF conocido y
    // sin resolver en todas las rutas 'web' con esos verbos (documentado y
    // confirmado también fuera de este módulo), no algo de este controller.
    // La lógica de esas acciones ya está cubierta a nivel de servicio en
    // TicketEmailChannelsRepositoryTest.

    public function test_controller_class_exists(): void
    {
        $this->assertTrue(class_exists(TicketEmailChannelsController::class));
    }

    public function test_repository_class_exists(): void
    {
        $this->assertTrue(class_exists(TicketEmailChannelsRepository::class));
    }

    public function test_index_route_registered(): void
    {
        $this->assertTrue(Route::has('manager.helpdesk.settings.email-channels.index'));
    }

    public function test_create_route_registered(): void
    {
        $this->assertTrue(Route::has('manager.helpdesk.settings.email-channels.create'));
    }

    public function test_edit_route_registered(): void
    {
        $this->assertTrue(Route::has('manager.helpdesk.settings.email-channels.edit'));
    }

    public function test_store_route_registered(): void
    {
        $this->assertTrue(Route::has('manager.helpdesk.settings.email-channels.store'));
    }

    public function test_update_route_registered(): void
    {
        $this->assertTrue(Route::has('manager.helpdesk.settings.email-channels.update'));
    }

    public function test_destroy_route_registered(): void
    {
        $this->assertTrue(Route::has('manager.helpdesk.settings.email-channels.destroy'));
    }

    public function test_test_route_registered(): void
    {
        $this->assertTrue(Route::has('manager.helpdesk.settings.email-channels.test'));
    }

    public function test_test_smtp_route_registered(): void
    {
        $this->assertTrue(Route::has('manager.helpdesk.settings.email-channels.test-smtp'));
    }

    public function test_sync_route_registered(): void
    {
        $this->assertTrue(Route::has('manager.helpdesk.settings.email-channels.sync'));
    }
}
