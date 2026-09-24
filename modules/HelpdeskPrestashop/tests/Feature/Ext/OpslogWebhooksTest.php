<?php

namespace Modules\HelpdeskPrestashop\Tests\Feature\Ext;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskPrestashop\Events\PsCartAbandoned;
use Modules\HelpdeskPrestashop\Events\PsOrderCreated;
use Modules\HelpdeskPrestashop\Events\PsOrderStatusChanged;
use Modules\HelpdeskPrestashop\Services\Ext\OpslogEventStore;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Pieza 38 · Eventos recibidos: el listener guarda lo que llega al webhook y
 * "Reprocesar" re-vincula los que quedaron sin cliente.
 *
 * Requiere la migración 2026_09_24_000001_opslog_create_helpdesk_ps_received_events_table.
 */
class OpslogWebhooksTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private string $url = '/api/helpdeskprestashop/webhooks/event';

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::connection('helpdesk')->hasTable(OpslogEventStore::TABLE)) {
            $this->markTestSkipped('Falta la migración de helpdesk_ps_received_events.');
        }

        config([
            'cache.default' => 'array',
            'helpdeskprestashop.api_url' => 'https://ps.test/modules/alsernetbridge/api.php',
            'helpdeskprestashop.webhook_secret' => 'test-secret',
        ]);

        // Los demás listeners de estos eventos no deben tocar nunca la tienda real.
        Http::fake();

        foreach (['helpdeskprestashop.ops.view', 'helpdeskprestashop.ops.events.reprocess', 'helpdesk.customers.view'] as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function postSignedEvent(string $event, array $data, int $timestamp, ?string $idempotencyKey = null): TestResponse
    {
        $body = json_encode(['action' => 'webhook.event', 'data' => $data]);
        $signature = hash_hmac('sha256', $timestamp.':'.$body, 'test-secret');

        return $this->call('POST', $this->url, [], [], [], array_filter([
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_ALSERNET_TIMESTAMP' => (string) $timestamp,
            'HTTP_X_ALSERNET_SIGNATURE' => $signature,
            'HTTP_X_ALSERNET_EVENT' => $event,
            'HTTP_X_ALSERNET_IDEMPOTENCY_KEY' => $idempotencyKey,
        ]), $body);
    }

    private function rows()
    {
        return DB::connection('helpdesk')->table(OpslogEventStore::TABLE);
    }

    private function user(string ...$permissions): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo($permissions);

        return $user;
    }

    public function test_an_order_for_an_unknown_customer_is_stored_as_pending(): void
    {
        $this->postSignedEvent('order.created', ['order_id' => 829611, 'customer_id' => 987654321, 'total' => 10.5], time())
            ->assertOk();

        $row = $this->rows()->where('subject_id', 829611)->where('event', 'order.created')->first();

        $this->assertNotNull($row);
        $this->assertSame('pending', $row->status);
        $this->assertSame('order', $row->subject_type);
        $this->assertSame('#829611', OpslogEventStore::subjectLabel($row));
    }

    public function test_a_known_customer_links_the_event_as_processed(): void
    {
        $customer = Customer::factory()->create(['email' => 'opslog-'.uniqid().'@example.com']);

        $this->postSignedEvent('cart.updated', ['customer_id' => 987654330, 'email' => $customer->email, 'cart_id' => 5821], time())
            ->assertOk();

        $row = $this->rows()->where('event', 'cart.updated')->where('subject_id', 5821)->first();

        $this->assertSame('processed', $row->status);
        $this->assertSame($customer->id, (int) $row->customer_id);
    }

    public function test_product_events_have_no_customer_and_are_processed(): void
    {
        $this->postSignedEvent('product.back_in_stock', ['product_id' => 114208, 'stock_quantity' => 3], time())
            ->assertOk();

        $this->assertSame('processed', $this->rows()->where('event', 'product.back_in_stock')->where('subject_id', 114208)->value('status'));
    }

    public function test_a_redelivery_with_the_same_idempotency_key_is_stored_once(): void
    {
        // Reenvío legítimo de PS: misma clave de idempotencia, firma nueva (la
        // misma firma dos veces la corta antes el anti-replay con un 401).
        $payload = ['order_id' => 700001, 'customer_id' => 987654322];
        $key = 'opslog-redelivery-'.uniqid();

        $this->postSignedEvent('order.created', $payload, time(), $key)->assertOk();
        $this->postSignedEvent('order.created', $payload, time() - 1, $key)->assertOk();

        $this->assertSame(1, $this->rows()->where('subject_id', 700001)->count());
    }

    public function test_recording_twice_with_the_same_dedup_key_updates_the_row(): void
    {
        $store = app(OpslogEventStore::class);
        $key = 'opslog-dedup-'.uniqid();

        $store->record('order.created', ['order_id' => 700002, 'customer_id' => 987654327], $key);
        $store->record('order.created', ['order_id' => 700002, 'customer_id' => 987654327, 'total' => 5], $key);

        $this->assertSame(1, $this->rows()->where('dedup_key', $key)->count());
    }

    public function test_reprocess_links_a_customer_that_now_exists_and_replays_the_event(): void
    {
        $store = app(OpslogEventStore::class);
        $store->record('order.created', ['order_id' => 829612, 'customer_id' => 987654323], 'opslog-test-'.uniqid());
        $id = (int) $this->rows()->where('subject_id', 829612)->value('id');

        $customer = Customer::factory()->create(['email' => 'opslog-'.uniqid().'@example.com']);
        $customer->linkExternalId('prestashop', '987654323');

        Event::fake([PsOrderCreated::class]);

        $this->actingAs($this->user('helpdeskprestashop.ops.view', 'helpdeskprestashop.ops.events.reprocess'))
            ->postJson(route('manager.helpdesk.ps.ext.opslog.events.reprocess', $id))
            ->assertOk()
            ->assertJsonPath('data.status', 'processed');

        Event::assertDispatched(PsOrderCreated::class, fn (PsOrderCreated $e) => $e->orderId() === 829612 && $e->email() === $customer->email);

        $row = $this->rows()->where('id', $id)->first();
        $this->assertSame('processed', $row->status);
        $this->assertSame($customer->id, (int) $row->customer_id);
        $this->assertSame(1, (int) $row->reprocess_count);
    }

    public function test_reprocess_keeps_pending_when_the_customer_still_does_not_exist(): void
    {
        app(OpslogEventStore::class)->record('order.created', ['order_id' => 829613, 'customer_id' => 987654324], 'opslog-test-'.uniqid());
        $id = (int) $this->rows()->where('subject_id', 829613)->value('id');

        Event::fake([PsOrderCreated::class]);

        $this->actingAs($this->user('helpdeskprestashop.ops.view', 'helpdeskprestashop.ops.events.reprocess'))
            ->postJson(route('manager.helpdesk.ps.ext.opslog.events.reprocess', $id))
            ->assertOk()
            ->assertJsonPath('data.status', 'pending');

        Event::assertNotDispatched(PsOrderCreated::class);
        $this->assertSame(1, (int) $this->rows()->where('id', $id)->value('reprocess_count'));
    }

    public function test_reprocess_links_but_does_not_replay_when_a_newer_event_of_the_same_order_exists(): void
    {
        $store = app(OpslogEventStore::class);
        $store->record('order.status_changed', ['order_id' => 829616, 'customer_id' => 987654328, 'new_status' => 3], 'opslog-test-'.uniqid());
        $store->record('order.status_changed', ['order_id' => 829616, 'customer_id' => 987654328, 'new_status' => 5], 'opslog-test-'.uniqid());
        $oldId = (int) $this->rows()->where('subject_id', 829616)->orderBy('id')->value('id');

        $customer = Customer::factory()->create(['email' => 'opslog-'.uniqid().'@example.com']);
        $customer->linkExternalId('prestashop', '987654328');

        Event::fake([PsOrderStatusChanged::class]);

        $this->actingAs($this->user('helpdeskprestashop.ops.view', 'helpdeskprestashop.ops.events.reprocess'))
            ->postJson(route('manager.helpdesk.ps.ext.opslog.events.reprocess', $oldId))
            ->assertOk()
            ->assertJsonPath('data.status', 'processed');

        // Repetir el estado viejo lo aplicaría encima del actual.
        Event::assertNotDispatched(PsOrderStatusChanged::class);
        $this->assertSame($customer->id, (int) $this->rows()->where('id', $oldId)->value('customer_id'));
    }

    public function test_reprocess_does_not_replay_events_older_than_the_replay_window(): void
    {
        app(OpslogEventStore::class)->record('cart.abandoned', ['cart_id' => 829617, 'customer_id' => 987654329], 'opslog-test-'.uniqid());
        $id = (int) $this->rows()->where('subject_id', 829617)->value('id');
        $this->rows()->where('id', $id)->update(['received_at' => now()->subDays(10)]);

        $customer = Customer::factory()->create(['email' => 'opslog-'.uniqid().'@example.com']);
        $customer->linkExternalId('prestashop', '987654329');

        Event::fake([PsCartAbandoned::class]);

        $this->actingAs($this->user('helpdeskprestashop.ops.view', 'helpdeskprestashop.ops.events.reprocess'))
            ->postJson(route('manager.helpdesk.ps.ext.opslog.events.reprocess', $id))
            ->assertOk()
            ->assertJsonPath('data.status', 'processed');

        Event::assertNotDispatched(PsCartAbandoned::class);
    }

    public function test_reprocess_requires_its_own_permission(): void
    {
        app(OpslogEventStore::class)->record('order.created', ['order_id' => 829614, 'customer_id' => 987654325], 'opslog-test-'.uniqid());
        $id = (int) $this->rows()->where('subject_id', 829614)->value('id');

        $this->actingAs($this->user('helpdeskprestashop.ops.view'))
            ->postJson(route('manager.helpdesk.ps.ext.opslog.events.reprocess', $id))
            ->assertForbidden();

        $this->assertSame('pending', $this->rows()->where('id', $id)->value('status'));
    }

    public function test_screen_lists_events_with_counts_and_is_forbidden_without_ops_view(): void
    {
        app(OpslogEventStore::class)->record('order.created', ['order_id' => 829615, 'customer_id' => 987654326], 'opslog-test-'.uniqid());

        $this->actingAs($this->user('helpdesk.customers.view'))
            ->get(route('manager.helpdesk.ps.ext.opslog.events'))
            ->assertForbidden();

        $this->actingAs($this->user('helpdeskprestashop.ops.view'))
            ->get(route('manager.helpdesk.ps.ext.opslog.events', ['status' => 'pending']))
            ->assertOk()
            ->assertSee('Eventos recibidos')
            ->assertSee('#829615')
            ->assertSee('sin cliente vinculado')
            // Sin permiso de reprocesar, el pendiente se ve pero sin botón.
            ->assertDontSee('>Reprocesar</button>', false);
    }
}
