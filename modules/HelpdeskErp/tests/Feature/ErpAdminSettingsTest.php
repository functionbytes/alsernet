<?php

namespace Modules\HelpdeskErp\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Cache;
use Modules\HelpdeskErp\Database\Seeders\HelpdeskErpPermissionsSeeder;
use Modules\HelpdeskErp\Models\ErpAdminSetting;
use Modules\HelpdeskErp\Services\ErpAdmin\ErpAdminChatMiddleware;
use Modules\HelpdeskErp\Services\ErpAdmin\ErpAdminSettingsOverrides;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * «Ajustes de Gestión»: permisos, guardado sobre config(), restablecer por
 * sección, auditoría y avisos del resumen desactivados.
 */
class ErpAdminSettingsTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private const URL = '/panel/helpdesk/erp/settings';

    protected function setUp(): void
    {
        parent::setUp();

        // Caché en memoria: los ajustes guardados en la transacción no pueden
        // quedarse en la caché compartida.
        config(['cache.default' => 'array']);
        Cache::flush();
        app()->forgetInstance('helpdeskerp.admin_settings.stored');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(HelpdeskErpPermissionsSeeder::class);

        if (! ErpAdminSettingsOverrides::ready()) {
            $this->markTestSkipped('Falta la migración de helpdesk_erp_settings.');
        }
    }

    protected function tearDown(): void
    {
        Cache::flush();

        parent::tearDown();
    }

    public function test_seeder_creates_extension_permissions(): void
    {
        $this->assertDatabaseHas('permissions', ['name' => 'helpdeskerp.settings.manage', 'guard_name' => 'web']);
        $this->assertDatabaseHas('permissions', ['name' => 'helpdeskerp.metrics.view', 'guard_name' => 'web']);
        $this->assertArrayHasKey('helpdeskerp.settings.manage', (array) config('helpdeskErp.ext_permissions'));
    }

    public function test_screen_requires_permission(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(self::URL)->assertForbidden();
        $this->actingAs($user)->post(self::URL, $this->payload())->assertForbidden();
        $this->actingAs($user)->post(self::URL.'/reset', ['section' => 'cache'])->assertForbidden();
    }

    public function test_manager_sees_the_screen(): void
    {
        $this->actingAs($this->manager())
            ->get(self::URL)
            ->assertOk()
            ->assertSee('Caché por estado')
            ->assertSee('Seguimiento de envíos');

        // Sin estilos en línea en las vistas del módulo (el layout del tema
        // sí trae alguno, así que no se mira la página entera).
        $this->assertAdminViewsHaveNoInlineStyles();
    }

    public function test_saving_applies_to_config_and_logs_activity(): void
    {
        $user = $this->manager();

        $this->actingAs($user)
            ->post(self::URL, $this->payload([
                'ttl' => ['ok' => 120],
                'alerts' => ['debt' => '0'],
                'linking' => ['auto' => '0'],
                'tracking' => [['carrier' => 'Tipsa Envíos', 'url' => 'https://tipsa.example/t?n={tracking}']],
            ]))
            ->assertRedirect(route('manager.helpdesk.erp.admin.settings.index'))
            ->assertSessionHas('success');

        $this->assertSame(120, config('helpdeskErp.chat_ttl.ok'));
        $this->assertFalse(config('helpdeskErp.chat_alerts.debt'));
        $this->assertFalse(config('helpdeskErp.auto_link'));
        $this->assertSame('https://tipsa.example/t?n={tracking}', config('helpdeskErp.tracking.templates.tipsaenvios'));
        $this->assertSame('tipsaenvios', config('helpdeskErp.tracking.aliases.tipsaenvios'));

        $this->assertDatabaseHas('helpdesk_erp_settings', ['key' => 'chat_ttl.ok', 'updated_by' => $user->id], 'helpdesk');
        // Lo que coincide con el valor por defecto no se guarda.
        $this->assertDatabaseMissing('helpdesk_erp_settings', ['key' => 'chat_ttl.detail'], 'helpdesk');

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'helpdeskerp-config',
            'description' => 'erp.settings.updated',
            'causer_id' => $user->id,
        ]);
    }

    public function test_invalid_values_are_rejected(): void
    {
        $before = ErpAdminSetting::query()->count();

        $this->actingAs($this->manager())
            ->from(self::URL)
            ->post(self::URL, $this->payload([
                'ttl' => ['ok' => -1],
                'tracking' => [['carrier' => 'x', 'url' => 'ftp://nope']],
            ]))
            ->assertRedirect(self::URL)
            // La fila nueva va detrás de las plantillas de serie del config.
            ->assertSessionHasErrors(['ttl.ok', 'tracking.'.count((array) ErpAdminSettingsOverrides::defaults()['tracking.urls']).'.url']);

        $this->assertSame($before, ErpAdminSetting::query()->count());
    }

    public function test_reset_returns_a_section_to_defaults(): void
    {
        $user = $this->manager();
        $default = (int) ErpAdminSettingsOverrides::defaults()['overview.orders_limit'];

        $this->actingAs($user)->post(self::URL, $this->payload(['overview' => ['orders_limit' => $default === 25 ? 26 : 25]]));
        $this->assertNotSame($default, config('helpdeskErp.chat_overview_orders_limit'));

        $this->actingAs($user)
            ->post(self::URL.'/reset', ['section' => 'overview'])
            ->assertRedirect(route('manager.helpdesk.erp.admin.settings.index'));

        $this->assertSame($default, config('helpdeskErp.chat_overview_orders_limit'));
        $this->assertDatabaseMissing('helpdesk_erp_settings', ['key' => 'overview.orders_limit'], 'helpdesk');
        $this->assertDatabaseHas('activity_log', ['log_name' => 'helpdeskerp-config', 'description' => 'erp.settings.reset']);
    }

    public function test_disabled_alerts_are_removed_from_the_overview(): void
    {
        config(['helpdeskErp.chat_alerts.debt' => false, 'helpdeskErp.chat_alerts.served' => true]);

        $route = new Route(['GET'], 'panel/helpdesk/customers/{customer}/erp/overview', []);
        $route->name('manager.helpdesk.erp.chat.overview');
        $request = Request::create('/panel/helpdesk/customers/1/erp/overview');
        $route->bind($request);
        $request->setRouteResolver(fn () => $route);

        $response = app(ErpAdminChatMiddleware::class)->handle($request, fn () => new JsonResponse([
            'success' => true,
            'state' => 'ok',
            'data' => ['sections' => [], 'alerts' => [
                ['code' => 'pending_debt', 'level' => 'warn', 'text' => 'Deuda'],
                ['code' => 'order_served', 'level' => 'good', 'text' => 'Servido'],
                ['code' => 'otro_codigo', 'level' => 'info', 'text' => 'Desconocido'],
            ]],
        ]));

        $this->assertSame(['order_served', 'otro_codigo'], array_column($response->getData(true)['data']['alerts'], 'code'));
    }

    private function manager(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo('helpdeskerp.settings.manage');

        return $user;
    }

    /**
     * Formulario completo con los valores en vigor, más los cambios.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        $d = ErpAdminSettingsOverrides::defaults();

        $tracking = [];
        foreach ((array) $d['tracking.urls'] as $carrier => $url) {
            $tracking[] = ['carrier' => $carrier, 'url' => $url];
        }

        $base = [
            'ttl' => [
                'ok' => $d['chat_ttl.ok'],
                'detail' => $d['chat_ttl.detail'],
                'blocked' => $d['chat_ttl.blocked'],
                'unavailable' => $d['chat_ttl.unavailable'],
                'down' => $d['chat_ttl.down'],
            ],
            'overview' => ['orders_limit' => $d['overview.orders_limit'], 'expiry_days' => $d['alerts.expiry_days']],
            'alerts' => array_map(fn ($on) => $on ? '1' : '0', (array) $d['alerts.enabled']),
            'linking' => ['auto' => $d['linking.auto'] ? '1' : '0'],
            'tracking' => $tracking,
            'metrics' => ['enabled' => $d['metrics.enabled'] ? '1' : '0', 'retention_days' => $d['metrics.retention_days']],
        ];

        if (isset($overrides['tracking'])) {
            $base['tracking'] = array_merge($tracking, $overrides['tracking']);
            unset($overrides['tracking']);
        }

        return array_replace_recursive($base, $overrides);
    }

    private function assertAdminViewsHaveNoInlineStyles(): void
    {
        $files = glob(module_path('HelpdeskErp', 'resources/views/admin/*.blade.php')) ?: [];
        $this->assertNotEmpty($files);

        foreach ($files as $file) {
            $this->assertStringNotContainsString('style="', (string) file_get_contents($file), basename($file).' tiene estilos en línea.');
        }
    }
}
