<?php

namespace Modules\HelpdeskErp\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskErp\Database\Seeders\HelpdeskErpPermissionsSeeder;
use Modules\HelpdeskErp\Services\ErpCross\ErpCrossShopMatcher;
use Modules\HelpdeskPrestashop\Services\PrestashopContextService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * GET panel/helpdesk/customers/{customer}/erp/timeline
 * (manager.helpdesk.erp.cross.timeline): línea de tiempo única del cliente.
 */
class ErpCrossTimelineTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private const ERP = '101544116';

    private const ALL = [
        'helpdeskerp.view',
        'helpdeskerp.orders.view',
        'helpdeskerp.finance.view',
        'helpdeskerp.loyalty.view',
        'helpdeskprestashop.orders.view',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'cache.default' => 'array',
            'queue.default' => 'sync',
            'helpdeskErp.manager_url' => 'http://manager.test',
            'helpdeskErp.bridge_token' => '',
        ]);
        Cache::flush();
        Http::preventStrayRequests();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(HelpdeskErpPermissionsSeeder::class);
        Permission::findOrCreate('helpdeskprestashop.orders.view', 'web');
        Permission::findOrCreate('helpdeskprestashop.view', 'web');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_requires_view_permission(): void
    {
        Http::fake();
        $customer = $this->customer(true);

        $this->actingAs($this->agent(['helpdeskerp.orders.view']))
            ->getJson($this->url($customer))
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_merges_erp_and_shop_events_sorted_with_source_states(): void
    {
        $this->fakeManager();
        $this->fakeShop();
        $customer = $this->customer(true);

        $res = $this->actingAs($this->agent(self::ALL))
            ->getJson($this->url($customer))
            ->assertOk()
            ->assertJsonPath('state', 'ok')
            ->assertJsonPath('data.sources.erp_invoices.state', 'blocked')
            ->assertJsonPath('data.sources.erp_orders.state', 'ok')
            ->assertJsonPath('data.sources.erp_points.state', 'ok');

        $items = $res->json('data.items');
        $types = array_column($items, 'type');

        $this->assertContains('erp_order', $types);
        $this->assertContains('erp_delivery_note', $types);
        $this->assertContains('erp_return', $types);
        $this->assertContains('erp_points', $types);
        $this->assertNotContains('erp_invoice', $types);

        // Orden descendente por fecha.
        $dates = array_column($items, 'date');
        $sorted = $dates;
        rsort($sorted);
        $this->assertSame($sorted, $dates);

        // La devolución ES el albarán de devolución: aparece una sola vez.
        $ids = array_column($items, 'id');
        $this->assertContains('erp_return:10101952174', $ids);
        $this->assertNotContains('erp_delivery_note:10101952174', $ids);

        // Cada elemento lleva cómo abrir su detalle.
        $byId = array_column($items, null, 'id');
        $this->assertSame(['kind' => 'erp_order', 'id' => '10102138690'], $byId['erp_order:10102138690']['open']);
        $this->assertSame('delivery_note', $byId['erp_points:100657179']['open']['kind']);
        $this->assertSame('10101961890', $byId['erp_points:100657179']['open']['id']);

        if (ErpCrossShopMatcher::prestashopAvailable()) {
            $this->assertContains('ps_order', $types);
            $this->assertSame(['kind' => 'ps_order', 'id' => '780002'], $byId['ps_order:780002']['open']);
        }
    }

    public function test_sections_without_permission_are_skipped(): void
    {
        $this->fakeManager();
        $this->fakeShop();
        $customer = $this->customer(true);

        $res = $this->actingAs($this->agent(['helpdeskerp.view', 'helpdeskerp.orders.view']))
            ->getJson($this->url($customer))
            ->assertOk()
            ->assertJsonPath('data.sources.erp_points.state', 'forbidden')
            ->assertJsonPath('data.sources.erp_invoices.state', 'forbidden')
            ->assertJsonPath('data.sources.ps_orders.state', ErpCrossShopMatcher::prestashopAvailable() ? 'forbidden' : 'unavailable');

        $types = array_column($res->json('data.items'), 'type');
        $this->assertNotContains('erp_points', $types);
        $this->assertNotContains('ps_order', $types);

        Http::assertNotSent(fn (Request $r) => str_contains($r->url(), '/loyalty-points'));
    }

    public function test_unlinked_customer_still_gets_a_timeline(): void
    {
        Http::fake();
        $this->fakeShop();
        $customer = $this->customer(false);

        $this->actingAs($this->agent(self::ALL))
            ->getJson($this->url($customer))
            ->assertOk()
            ->assertJsonPath('data.erp_id', null)
            ->assertJsonPath('data.sources.erp_orders.state', 'unlinked');

        Http::assertNothingSent();
    }

    /* ── Fixtures ─────────────────────────────────────────────────────── */

    private function fakeShop(): void
    {
        if (! class_exists(PrestashopContextService::class)) {
            return;
        }

        $ps = $this->mock(PrestashopContextService::class);
        $ps->shouldReceive('getCustomerOrders')->andReturn(['data' => [
            ['id' => 780002, 'reference' => 'FBNZUDHEJ', 'total' => 120.98, 'payment' => 'Bizum', 'state_name' => 'Enviado', 'created_at' => '2025-09-16 20:06:39'],
        ]]);
        $ps->shouldReceive('peekCachedContext')->andReturnNull();
    }

    private function fakeManager(): void
    {
        Http::fake(function (Request $request) {
            $path = (string) parse_url($request->url(), PHP_URL_PATH);
            $sub = trim(substr($path, strlen('/api/erp/customer/'.self::ERP)), '/');

            return match ($sub) {
                '' => Http::response(['success' => true, 'data' => ['id' => (int) self::ERP, 'code_internet' => '911230', 'available' => true]]),
                'orders' => Http::response(['success' => true, 'data' => [
                    ['id' => '10102138690', 'number' => '44398', 'status' => '7', 'date' => '2025-09-16 20:06:39', 'served_date' => '2025-09-18 00:00:00'],
                ], 'pagination' => ['limit' => 50, 'offset' => 0, 'count' => 1, 'hasMore' => false]]),
                'delivery-notes' => Http::response(['success' => true, 'data' => [
                    ['id' => 10101961890, 'delivery_id' => '101961890', 'number' => '51498', 'status' => true, 'invoice_id' => null, 'date' => '2025-11-11 00:00:00', 'created' => '2025-11-11 15:41:55'],
                    ['id' => 10101952174, 'delivery_id' => '101952174', 'number' => '2817', 'status' => true, 'type' => '3', 'date' => '2025-09-30 15:43:47', 'created' => '2025-09-30 15:43:47'],
                ], 'pagination' => ['limit' => 50, 'offset' => 0, 'count' => 2, 'hasMore' => false]]),
                'returns' => Http::response(['success' => true, 'data' => [
                    ['id' => 10101952174, 'kind' => 'devolucion', 'number' => '2817', 'date' => '2025-09-30 15:43:47', 'amount' => -49.99,
                        'delivery_note_id' => 10101949389, 'invoice_id' => null, 'order_id' => 10102138690],
                ], 'pagination' => ['limit' => 50, 'offset' => 0, 'count' => 1, 'hasMore' => false]]),
                'loyalty-points' => Http::response(['success' => true, 'data' => ['id' => (int) self::ERP, 'balance' => 40, 'movements' => [
                    ['id' => 100657179, 'card' => '100404753', 'liquidation' => '100001248', 'delivery_id' => '101961890', 'points' => 40, 'date' => '2025-11-11 00:00:00', 'available' => true],
                ]]]),
                default => Http::response(['success' => false, 'error' => 'Acceso denegado a la tabla Oracle. Solicitar GRANT SELECT al DBA.'], 200),
            };
        });
    }

    private function url(Customer $customer): string
    {
        return '/panel/helpdesk/customers/'.$customer->id.'/erp/timeline';
    }

    /**
     * @param  list<string>  $permissions
     */
    private function agent(array $permissions): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo([...$permissions, 'helpdesk.customers.manage']);

        return $user;
    }

    private function customer(bool $linked): Customer
    {
        $customer = Customer::factory()->create([
            'email' => Str::lower(Str::replace('-', '', (string) Str::uuid())).'@test.example',
        ]);

        if ($linked) {
            $customer->linkExternalId('erp', self::ERP);
            $customer->linkExternalId('prestashop', '911230');
        }

        return $customer->fresh();
    }
}
