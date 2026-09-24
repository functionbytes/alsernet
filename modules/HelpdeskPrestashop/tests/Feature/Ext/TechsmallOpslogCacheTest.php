<?php

namespace Modules\HelpdeskPrestashop\Tests\Feature\Ext;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * techsmall · Registro del puente con la marca cache_hit (alsernetbridge
 * 1.2.5): la pantalla recibe las respuestas de caché separadas y, sin la
 * columna, sigue avisando de que la media las incluye.
 */
class TechsmallOpslogCacheTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private string $apiUrl = 'https://ps.test/modules/alsernetbridge/api.php';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'cache.default' => 'array',
            'helpdeskprestashop.api_url' => $this->apiUrl,
            'helpdeskprestashop.webhook_secret' => 'test-secret-for-hmac',
            'helpdeskprestashop.http_timeout' => 25,
        ]);

        Permission::firstOrCreate(['name' => 'helpdeskprestashop.ops.view', 'guard_name' => 'web']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function viewer(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo('helpdeskprestashop.ops.view');

        return $user;
    }

    public function test_cache_hits_reach_the_screen_when_the_bridge_tracks_them(): void
    {
        Http::fake([$this->apiUrl => Http::response(['ok' => true, 'data' => [
            'window_hours' => 24, 'timeout_ms' => 25000, 'cache_tracked' => true,
            'stats' => ['calls' => 100, 'avg_ms' => 420, 'errors' => 0, 'timeouts' => 0, 'failures' => 0, 'rejected' => 0, 'cache_hits' => 37, 'real_calls' => 63],
            'by_action' => [['action' => 'customer.orders', 'calls' => 50, 'avg_ms' => 300, 'max_ms' => 900, 'failures' => 0, 'cache_hits' => 20]],
            'rows' => [['id' => 9, 'action' => 'customer.orders', 'id_customer' => 5, 'status' => 200, 'ms' => 3, 'result' => 'cache', 'error' => null, 'at' => '2026-09-24T10:00:00+02:00']],
            'queue' => ['pending' => 0, 'due' => 0, 'dead' => 0, 'recent_dead' => []],
        ]])]);

        $this->actingAs($this->viewer())
            ->getJson(route('manager.helpdesk.ps.ext.opslog.bridge-log.data', ['result' => 'cache']))
            ->assertOk()
            ->assertJsonPath('data.cache_tracked', true)
            ->assertJsonPath('data.stats.cache_hits', 37)
            ->assertJsonPath('data.by_action.0.cache_hits', 20)
            ->assertJsonPath('data.rows.0.result', 'cache');

        Http::assertSent(fn ($r) => ($r->data()['action'] ?? null) === 'opslog.bridge_log'
            && ($r->data()['result'] ?? null) === 'cache');
    }

    public function test_screen_explains_the_pending_upgrade_when_cache_is_not_tracked(): void
    {
        $this->actingAs($this->viewer())
            ->get(route('manager.helpdesk.ps.ext.opslog.bridge-log'))
            ->assertOk()
            ->assertSee('pscOpslogCacheChip', false)
            ->assertSee('data-kpi="calls-note"', false)
            ->assertSee('alsernetbridge a la versión 1.2.5');
    }
}
