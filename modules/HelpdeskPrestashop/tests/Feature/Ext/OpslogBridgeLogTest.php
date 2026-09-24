<?php

namespace Modules\HelpdeskPrestashop\Tests\Feature\Ext;

use App\Models\User;
use Illuminate\Foundation\Console\QueuedCommand;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Pieza 37 · Registro del puente: lectura del log del puente (opslog.bridge_log),
 * reintento de webhooks muertos (opslog.requeue_dead) y calentado en cola.
 * El puente siempre está simulado con Http::fake.
 */
class OpslogBridgeLogTest extends TestCase
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

        foreach ([
            'helpdeskprestashop.ops.view',
            'helpdeskprestashop.ops.webhooks.retry',
            'helpdeskprestashop.ops.cache.warm',
            'helpdeskprestashop.ops.events.reprocess',
        ] as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function user(string ...$permissions): User
    {
        $user = User::factory()->create();
        if ($permissions !== []) {
            $user->givePermissionTo($permissions);
        }

        return $user;
    }

    private function bridgeData(array $queue = ['pending' => 0, 'due' => 0, 'dead' => 2, 'recent_dead' => []]): array
    {
        return [
            'window_hours' => 24,
            'timeout_ms' => 25000,
            'cache_tracked' => false,
            'stats' => ['calls' => 1284, 'avg_ms' => 310, 'errors' => 4, 'timeouts' => 3, 'failures' => 7, 'rejected' => 12, 'cache_hits' => null],
            'by_action' => [['action' => 'order.detail', 'calls' => 40, 'avg_ms' => 1902, 'max_ms' => 4000, 'failures' => 0]],
            'rows' => [['id' => 1, 'action' => 'customer.wishlist', 'id_customer' => 5, 'status' => 200, 'ms' => 26004, 'result' => 'timeout', 'error' => null, 'at' => '2026-09-24T10:00:00+02:00']],
            'queue' => $queue,
        ];
    }

    public function test_screen_and_data_are_forbidden_without_ops_view(): void
    {
        Http::fake();
        $user = $this->user();

        $this->actingAs($user)->get(route('manager.helpdesk.ps.ext.opslog.bridge-log'))->assertForbidden();
        $this->actingAs($user)->getJson(route('manager.helpdesk.ps.ext.opslog.bridge-log.data'))->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_screen_renders_for_ops_viewer(): void
    {
        $this->actingAs($this->user('helpdeskprestashop.ops.view'))
            ->get(route('manager.helpdesk.ps.ext.opslog.bridge-log'))
            ->assertOk()
            ->assertSee('Registro del puente')
            ->assertSee('pscOpslogBridge', false)
            // Sin permiso de escritura no se pintan los botones de operación.
            ->assertDontSee('Calentar caché')
            ->assertDontSee('Reintentar fallidas');
    }

    public function test_data_reads_the_bridge_log_with_the_laravel_timeout_as_threshold(): void
    {
        Http::fake([$this->apiUrl => Http::response(['ok' => true, 'data' => $this->bridgeData()])]);

        $this->actingAs($this->user('helpdeskprestashop.ops.view'))
            ->getJson(route('manager.helpdesk.ps.ext.opslog.bridge-log.data', ['hours' => 168, 'result' => 'failures']))
            ->assertOk()
            ->assertJsonPath('data.stats.failures', 7)
            ->assertJsonPath('data.rows.0.result', 'timeout');

        Http::assertSent(function (Request $request) {
            $data = $request->data();

            return ($data['action'] ?? null) === 'opslog.bridge_log'
                && ($data['hours'] ?? null) === 168
                && ($data['result'] ?? null) === 'failures'
                && ($data['timeout_ms'] ?? null) === 25000
                // Lectura: nunca lleva clave de idempotencia.
                && ! $request->hasHeader('X-Alsernet-Idempotency-Key');
        });
    }

    public function test_data_rejects_unknown_window(): void
    {
        Http::fake();

        $this->actingAs($this->user('helpdeskprestashop.ops.view'))
            ->getJson(route('manager.helpdesk.ps.ext.opslog.bridge-log.data', ['hours' => 5000]))
            ->assertStatus(422);

        Http::assertNothingSent();
    }

    public function test_data_reports_503_when_the_bridge_is_down(): void
    {
        Http::fake([$this->apiUrl => Http::response('boom', 500)]);

        $this->actingAs($this->user('helpdeskprestashop.ops.view'))
            ->getJson(route('manager.helpdesk.ps.ext.opslog.bridge-log.data'))
            ->assertStatus(503)
            ->assertJsonPath('success', false);
    }

    public function test_requeue_requires_its_own_permission(): void
    {
        Http::fake();

        $this->actingAs($this->user('helpdeskprestashop.ops.view'))
            ->postJson(route('manager.helpdesk.ps.ext.opslog.bridge-log.requeue'))
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_requeue_sends_the_write_action_with_idempotency_key(): void
    {
        Http::fake([$this->apiUrl => Http::response(['ok' => true, 'data' => [
            'requeued' => 2, 'skipped' => 0, 'events' => ['order.created' => 2],
            'queue' => ['pending' => 2, 'due' => 2, 'dead' => 0, 'recent_dead' => []],
        ]])]);

        $user = $this->user('helpdeskprestashop.ops.view', 'helpdeskprestashop.ops.webhooks.retry');
        // La clave de la pantalla se ata a la acción y al usuario: la tabla de
        // idempotencia del puente es común a todas las acciones.
        $expectedKey = sha1('opslog.requeue_dead:'.$user->getAuthIdentifier().':opslog-test-key');

        $this->actingAs($user)
            ->withHeader('Idempotency-Key', 'opslog-test-key')
            ->postJson(route('manager.helpdesk.ps.ext.opslog.bridge-log.requeue'))
            ->assertOk()
            ->assertJsonPath('data.requeued', 2)
            ->assertJsonPath('message', '2 webhooks vuelven a la cola.');

        Http::assertSent(fn (Request $request) => ($request->data()['action'] ?? null) === 'opslog.requeue_dead'
            && ($request->header('X-Alsernet-Idempotency-Key')[0] ?? null) === $expectedKey);
    }

    public function test_warm_cache_is_queued_not_run_in_the_request_and_has_a_cooldown(): void
    {
        Queue::fake();
        Http::fake();

        $user = $this->user('helpdeskprestashop.ops.view', 'helpdeskprestashop.ops.cache.warm');

        $this->actingAs($user)
            ->postJson(route('manager.helpdesk.ps.ext.opslog.bridge-log.warm-cache'))
            ->assertOk()
            ->assertJsonPath('success', true);

        Queue::assertPushedOn('helpdesk-ps-warming', QueuedCommand::class);
        Http::assertNothingSent();

        $this->actingAs($user)
            ->postJson(route('manager.helpdesk.ps.ext.opslog.bridge-log.warm-cache'))
            ->assertStatus(429);

        Queue::assertPushed(QueuedCommand::class, 1);
    }

    public function test_warm_cache_requires_its_own_permission(): void
    {
        Queue::fake();

        $this->actingAs($this->user('helpdeskprestashop.ops.view'))
            ->postJson(route('manager.helpdesk.ps.ext.opslog.bridge-log.warm-cache'))
            ->assertForbidden();

        Queue::assertNothingPushed();
    }
}
