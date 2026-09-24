<?php

namespace Modules\HelpdeskPrestashop\Tests\Feature\Ext;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\HelpdeskPrestashop\Database\Seeders\HelpdeskPrestashopPermissionsSeeder;
use Modules\HelpdeskPrestashop\Services\Ext\SettingsOverrides;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

/**
 * «Ajustes del chat» (extensión "settings"): pantalla, validación,
 * guardado solo de lo que cambia, auditoría, restablecer y aplicación de
 * los overrides sobre config().
 */
class SettingsScreenTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    protected function setUp(): void
    {
        parent::setUp();

        // Caché en memoria: los overrides se cachean y nada de esto puede
        // acabar en la caché real (nota del proyecto sobre Setting::set).
        config(['cache.default' => 'array']);

        $this->seed(HelpdeskPrestashopPermissionsSeeder::class);

        // Punto de partida limpio dentro de la transacción: sin overrides.
        if (SettingsOverrides::ready()) {
            DB::connection(SettingsOverrides::CONNECTION)->table(SettingsOverrides::TABLE)->delete();
            SettingsOverrides::refresh();
        }
    }

    private function requireTable(): void
    {
        if (! SettingsOverrides::ready()) {
            $this->markTestSkipped('Falta la migración 2026_09_25_000002_settings_create_helpdesk_ps_settings_table.');
        }
    }

    private function user(string ...$permissions): User
    {
        $user = User::factory()->create();
        if ($permissions !== []) {
            $user->givePermissionTo($permissions);
        }

        return $user;
    }

    private function manager(): User
    {
        return $this->user('helpdeskprestashop.settings.manage');
    }

    /**
     * Formulario tal y como lo envía la pantalla con los valores por defecto.
     */
    private function payload(array $overrides = []): array
    {
        $d = SettingsOverrides::defaults();
        $ri = (array) $d['refunds.return_instructions'];

        $reasons = [];
        foreach ((array) $d['vouchers.reasons'] as $key => $label) {
            $reasons[] = ['key' => $key, 'label' => $label];
        }

        $base = [
            'vouchers' => [
                'agent_limit' => $d['vouchers.agent_limit'],
                'approver_limit' => $d['vouchers.approver_limit'],
                'validity_days' => implode(', ', (array) $d['vouchers.validity_days']),
                'reasons' => $reasons,
            ],
            'refunds' => [
                'agent_limit' => $d['refunds.agent_limit'],
                'approver_limit' => $d['refunds.approver_limit'],
                'carrier' => $ri['carrier'] ?? '',
                'address' => implode("\n", array_filter(explode('|', (string) ($ri['address'] ?? '')), 'strlen')),
                'validity_days' => $ri['validity_days'] ?? 14,
                'steps' => implode("\n", (array) ($ri['steps'] ?? [])),
            ],
            'replies' => (array) $d['quick_replies'],
        ];

        // Las listas (respuestas) se sustituyen enteras, no se mezclan por índice.
        $replies = $overrides['replies'] ?? null;
        unset($overrides['replies']);
        $merged = array_replace_recursive($base, $overrides);
        if ($replies !== null) {
            $merged['replies'] = $replies;
        }

        return $merged;
    }

    public function test_screen_requires_settings_permission(): void
    {
        $this->actingAs($this->user('helpdeskprestashop.orders.view'))
            ->get(route('manager.helpdesk.ps.ext.settings.index'))
            ->assertForbidden();

        $this->actingAs($this->manager())
            ->get(route('manager.helpdesk.ps.ext.settings.index'))
            ->assertOk()
            ->assertSee('Vales de compensación')
            ->assertSee('Respuestas rápidas con datos reales')
            ->assertSee('{importe_reembolso}', false);
    }

    public function test_only_manager_roles_get_the_permission(): void
    {
        $roles = (array) config('helpdeskprestashop.ext.settings.role_permissions');

        $this->assertSame(['helpdesk-manager', 'helpdesk-admin'], array_keys($roles));
    }

    public function test_update_requires_permission(): void
    {
        $this->actingAs($this->user('helpdeskprestashop.vouchers.approve'))
            ->post(route('manager.helpdesk.ps.ext.settings.update'), $this->payload())
            ->assertForbidden();
    }

    public function test_save_stores_only_changed_keys_applies_them_and_audits(): void
    {
        $this->requireTable();

        $this->actingAs($this->manager())
            ->post(route('manager.helpdesk.ps.ext.settings.update'), $this->payload([
                'vouchers' => ['agent_limit' => 40, 'validity_days' => '15, 30'],
                'refunds' => ['approver_limit' => 750, 'address' => "Almacén de devoluciones\nCalle Falsa 1\n15001 A Coruña"],
            ]))
            ->assertRedirect(route('manager.helpdesk.ps.ext.settings.index'))
            ->assertSessionHasNoErrors();

        $keys = DB::connection(SettingsOverrides::CONNECTION)->table(SettingsOverrides::TABLE)->pluck('key')->sort()->values()->all();
        $this->assertSame(['refunds.approver_limit', 'refunds.return_instructions', 'vouchers.agent_limit', 'vouchers.validity_days'], $keys);

        // Aplicado ya en config (lo que lee el resto del módulo).
        $this->assertEquals(40.0, config('helpdeskprestashop.vouchers.agent_limit'));
        $this->assertSame([15, 30], config('helpdeskprestashop.vouchers.validity_days'));
        $this->assertEquals(750.0, config('helpdeskprestashop.ext.refunds.approver_limit'));
        $this->assertSame('Almacén de devoluciones|Calle Falsa 1|15001 A Coruña', config('helpdeskprestashop.ext.refunds.return_instructions.address'));

        $this->assertTrue(Activity::query()
            ->where('log_name', 'helpdeskprestashop-config')
            ->where('description', 'ps.settings.updated')
            ->exists());
    }

    public function test_saving_the_default_value_removes_the_override(): void
    {
        $this->requireTable();
        $manager = $this->manager();

        $this->actingAs($manager)->post(route('manager.helpdesk.ps.ext.settings.update'), $this->payload(['vouchers' => ['agent_limit' => 40]]));
        $this->assertSame(1, DB::connection(SettingsOverrides::CONNECTION)->table(SettingsOverrides::TABLE)->count());

        $this->actingAs($manager)->post(route('manager.helpdesk.ps.ext.settings.update'), $this->payload());
        $this->assertSame(0, DB::connection(SettingsOverrides::CONNECTION)->table(SettingsOverrides::TABLE)->count());
        $this->assertEquals(SettingsOverrides::defaults()['vouchers.agent_limit'], config('helpdeskprestashop.vouchers.agent_limit'));
    }

    public function test_validation_is_strict(): void
    {
        $this->requireTable();
        $manager = $this->manager();
        $url = route('manager.helpdesk.ps.ext.settings.update');

        $this->actingAs($manager)->post($url, $this->payload(['vouchers' => ['agent_limit' => 100, 'approver_limit' => 50]]))
            ->assertSessionHasErrors('vouchers.approver_limit');

        $this->actingAs($manager)->post($url, $this->payload(['vouchers' => ['approver_limit' => 900]]))
            ->assertSessionHasErrors('vouchers.approver_limit');

        $this->actingAs($manager)->post($url, $this->payload(['vouchers' => ['validity_days' => '30, abc']]))
            ->assertSessionHasErrors('vouchers.validity_days');

        $this->actingAs($manager)->post($url, $this->payload(['vouchers' => ['reasons' => [['key' => 'Con Espacios', 'label' => 'x']]]]))
            ->assertSessionHasErrors('vouchers.reasons.0.key');

        $this->actingAs($manager)->post($url, $this->payload(['replies' => [['t' => 'Hola', 's' => '', 'text' => 'Tu pedido {pedidos}']]]))
            ->assertSessionHasErrors('replies.0.text');

        $this->actingAs($manager)->post($url, $this->payload(['refunds' => ['address' => 'Línea con | barra']]))
            ->assertSessionHasErrors('refunds.address');

        $this->assertSame(0, DB::connection(SettingsOverrides::CONNECTION)->table(SettingsOverrides::TABLE)->count());
    }

    public function test_reset_section_goes_back_to_config(): void
    {
        $this->requireTable();
        $manager = $this->manager();

        $this->actingAs($manager)->post(route('manager.helpdesk.ps.ext.settings.update'), $this->payload([
            'vouchers' => ['agent_limit' => 40],
            'refunds' => ['agent_limit' => 80],
        ]));

        $this->actingAs($manager)
            ->post(route('manager.helpdesk.ps.ext.settings.reset'), ['section' => 'vouchers'])
            ->assertRedirect(route('manager.helpdesk.ps.ext.settings.index'));

        $keys = DB::connection(SettingsOverrides::CONNECTION)->table(SettingsOverrides::TABLE)->pluck('key')->all();
        $this->assertSame(['refunds.agent_limit'], $keys);

        $this->actingAs($manager)
            ->post(route('manager.helpdesk.ps.ext.settings.reset'), ['section' => 'everything'])
            ->assertSessionHasErrors('section');
    }

    public function test_apply_reads_the_table_and_overrides_config(): void
    {
        $this->requireTable();

        DB::connection(SettingsOverrides::CONNECTION)->table(SettingsOverrides::TABLE)->insert([
            'key' => 'refunds.agent_limit',
            'value' => json_encode(99),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        // Fila con forma rota: se ignora y vale el default.
        DB::connection(SettingsOverrides::CONNECTION)->table(SettingsOverrides::TABLE)->insert([
            'key' => 'vouchers.reasons',
            'value' => json_encode('no es un mapa'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Cache::forget(SettingsOverrides::CACHE_KEY);
        app()->forgetInstance('helpdeskprestashop.ps_settings.stored');
        SettingsOverrides::apply();

        $this->assertEquals(99.0, config('helpdeskprestashop.ext.refunds.agent_limit'));
        $this->assertSame(SettingsOverrides::defaults()['vouchers.reasons'], config('helpdeskprestashop.vouchers.reasons'));
    }

    public function test_inbox_view_passes_saved_quick_replies(): void
    {
        $this->requireTable();

        $this->actingAs($this->manager())->post(route('manager.helpdesk.ps.ext.settings.update'), $this->payload([
            'replies' => [['t' => 'Seguimiento', 's' => '{transportista}', 'text' => 'Hola {cliente}, tu envío es {seguimiento}']],
        ]));

        $html = view('helpdeskprestashop::modals.ext.settings')->render();

        $this->assertStringContainsString('id="psSettingsCfg"', $html);
        $this->assertStringContainsString('Hola {cliente}, tu envío es {seguimiento}', html_entity_decode($html));
        $this->assertStringNotContainsString('Estado del pedido', html_entity_decode($html));
    }
}
