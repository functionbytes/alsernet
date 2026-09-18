<?php

namespace Modules\Helpdesk\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Core\Http\Middleware\VerifyCsrfToken;
use Modules\Helpdesk\Database\Seeders\PermissionsSeeder;
use Modules\Helpdesk\Models\OffHoursResponse;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OffHoursResponsesControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['mariadb', 'helpdesk'];

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        // CSRF real desactivado para el cliente de test: el resto de la suite
        // HTTP del módulo (p. ej. RoutingRulesControllerTest) también recibe
        // 419 sin esto — no es un problema introducido por estas páginas.
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $this->seed(PermissionsSeeder::class);

        $role = Role::firstOrCreate(['name' => 'super-settings', 'guard_name' => 'web']);

        $this->manager = User::factory()->create();
        $this->manager->assignRole($role);

        // Tabla sin seeder ligado a las migraciones: puede haber filas reales
        // configuradas por un admin. Se limpia dentro de la transacción del
        // test para no depender del estado externo (mismo patrón que
        // RespondOffHoursOnConversationCreatedTest).
        OffHoursResponse::query()->delete();
    }

    // ─── index ────────────────────────────────────────────────────────────────

    public function test_guest_cannot_access_index(): void
    {
        $this->get(route('settings.helpdesk.business.off-hours'))
            ->assertRedirect(route('auth.login'));
    }

    public function test_user_without_permission_cannot_access_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('settings.helpdesk.business.off-hours'))
            ->assertForbidden();
    }

    public function test_manager_can_view_index(): void
    {
        $this->actingAs($this->manager)
            ->get(route('settings.helpdesk.business.off-hours'))
            ->assertOk();
    }

    // ─── store ────────────────────────────────────────────────────────────────

    public function test_manager_can_create_off_hours_response(): void
    {
        $payload = [
            'channel' => 'whatsapp',
            'language' => 'en',
            'message' => 'We are currently closed.',
            'is_active' => '1',
        ];

        $this->actingAs($this->manager)
            ->post(route('settings.helpdesk.off-hours-responses.store'), $payload)
            ->assertRedirect(route('settings.helpdesk.business.off-hours'));

        $this->assertDatabaseHas('helpdesk_off_hours_responses', [
            'channel' => 'whatsapp',
            'language' => 'en',
            'message' => 'We are currently closed.',
            'is_active' => true,
        ], 'helpdesk');
    }

    public function test_manager_can_create_generic_off_hours_response_with_empty_selects(): void
    {
        // El <select> de "Todos los canales" / "Automático" manda value=""
        // (string vacío), no un campo ausente.
        $payload = [
            'channel' => '',
            'language' => '',
            'message' => 'Estamos fuera de horario.',
        ];

        $this->actingAs($this->manager)
            ->post(route('settings.helpdesk.off-hours-responses.store'), $payload)
            ->assertRedirect(route('settings.helpdesk.business.off-hours'));

        $this->assertDatabaseHas('helpdesk_off_hours_responses', [
            'channel' => null,
            'language' => null,
            'message' => 'Estamos fuera de horario.',
        ], 'helpdesk');
    }

    public function test_store_fails_without_message(): void
    {
        $this->actingAs($this->manager)
            ->post(route('settings.helpdesk.off-hours-responses.store'), [])
            ->assertSessionHasErrors(['message'], null, 'offHours');
    }

    public function test_store_fails_with_invalid_channel(): void
    {
        $this->actingAs($this->manager)
            ->post(route('settings.helpdesk.off-hours-responses.store'), [
                'channel' => 'telegram',
                'message' => 'Mensaje',
            ])
            ->assertSessionHasErrors(['channel'], null, 'offHours');
    }

    public function test_store_fails_with_invalid_language(): void
    {
        $this->actingAs($this->manager)
            ->post(route('settings.helpdesk.off-hours-responses.store'), [
                'language' => 'zz',
                'message' => 'Mensaje',
            ])
            ->assertSessionHasErrors(['language'], null, 'offHours');
    }

    public function test_store_fails_for_duplicate_channel_language_combination(): void
    {
        OffHoursResponse::query()->create([
            'channel' => 'web',
            'language' => 'es',
            'message' => 'Ya existente',
            'is_active' => true,
        ]);

        $this->actingAs($this->manager)
            ->post(route('settings.helpdesk.off-hours-responses.store'), [
                'channel' => 'web',
                'language' => 'es',
                'message' => 'Duplicado',
            ])
            ->assertRedirect(route('settings.helpdesk.business.off-hours'))
            ->assertSessionHas('error');

        $this->assertSame(1, OffHoursResponse::query()->where('channel', 'web')->where('language', 'es')->count());
    }

    public function test_store_fails_for_user_without_permission(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('settings.helpdesk.off-hours-responses.store'), [
                'message' => 'Mensaje',
            ])
            ->assertForbidden();
    }

    // ─── update ───────────────────────────────────────────────────────────────

    public function test_manager_can_update_off_hours_response(): void
    {
        $response = OffHoursResponse::query()->create([
            'channel' => null,
            'language' => null,
            'message' => 'Original',
            'is_active' => true,
        ]);

        $this->actingAs($this->manager)
            ->put(route('settings.helpdesk.off-hours-responses.update', $response), [
                'channel' => 'facebook',
                'language' => 'fr',
                'message' => 'Actualizado',
                'is_active' => '0',
            ])
            ->assertRedirect(route('settings.helpdesk.business.off-hours'));

        $response->refresh();
        $this->assertSame('facebook', $response->channel);
        $this->assertSame('fr', $response->language);
        $this->assertSame('Actualizado', $response->message);
        $this->assertFalse($response->is_active);
    }

    public function test_update_fails_for_user_without_permission(): void
    {
        $response = OffHoursResponse::query()->create([
            'message' => 'Protegido',
            'is_active' => true,
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('settings.helpdesk.off-hours-responses.update', $response), [
                'message' => 'Modificado',
            ])
            ->assertForbidden();
    }

    // ─── destroy ──────────────────────────────────────────────────────────────

    public function test_manager_can_delete_off_hours_response(): void
    {
        $response = OffHoursResponse::query()->create([
            'message' => 'A eliminar',
            'is_active' => true,
        ]);

        $this->actingAs($this->manager)
            ->delete(route('settings.helpdesk.off-hours-responses.destroy', $response))
            ->assertRedirect(route('settings.helpdesk.business.off-hours'));

        $this->assertDatabaseMissing('helpdesk_off_hours_responses', ['id' => $response->id], 'helpdesk');
    }

    public function test_destroy_fails_for_user_without_permission(): void
    {
        $response = OffHoursResponse::query()->create([
            'message' => 'No eliminar',
            'is_active' => true,
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->delete(route('settings.helpdesk.off-hours-responses.destroy', $response))
            ->assertForbidden();
    }
}
