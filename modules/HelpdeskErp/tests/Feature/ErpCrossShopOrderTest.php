<?php

namespace Modules\HelpdeskErp\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Mockery;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskErp\Database\Seeders\HelpdeskErpPermissionsSeeder;
use Modules\HelpdeskErp\Services\ErpCross\ErpCrossShopMatcher;
use Modules\HelpdeskPrestashop\Services\PrestashopContextService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * GET panel/helpdesk/customers/{customer}/erp/orders/{orderId}/shop
 * (manager.helpdesk.erp.cross.shop-order): pedido de Gestión → pedido de la
 * tienda. Manager, API de Gestión (InterGes) y bridge de PrestaShop falsos.
 */
class ErpCrossShopOrderTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private const ERP = '101544116';

    private const ORDER = 10102138690;

    private const PERMS = ['helpdeskerp.view', 'helpdeskerp.orders.view', 'helpdeskprestashop.orders.view'];

    protected function setUp(): void
    {
        parent::setUp();

        if (! ErpCrossShopMatcher::prestashopAvailable()) {
            $this->markTestSkipped('HelpdeskPrestashop no está activo en esta instalación.');
        }

        config([
            'cache.default' => 'array',
            'queue.default' => 'sync',
            'helpdeskErp.manager_url' => 'http://manager.test',
            'helpdeskErp.bridge_token' => '',
            'helpdeskErp.ext.cross.gestion_url' => 'http://gestion.test',
        ]);
        Cache::flush();
        Http::preventStrayRequests();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(HelpdeskErpPermissionsSeeder::class);
        Permission::findOrCreate('helpdeskprestashop.orders.view', 'web');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_matches_by_origin_id_confirmed_by_the_shop(): void
    {
        $this->fakeUpstream(originId: '780002');
        $customer = $this->linkedCustomer();

        $ps = $this->mockPs();
        $ps->shouldReceive('getOrderDetail')
            ->once()
            ->with(780002, $customer->email, 911230)
            ->andReturn(['id' => 780002, 'reference' => 'FBNZUDHEJ', 'customer_id' => 911230]);

        $this->actingAs($this->agent(self::PERMS))
            ->getJson($this->url($customer))
            ->assertOk()
            ->assertJsonPath('state', 'ok')
            ->assertJsonPath('data.ps_order_id', 780002)
            ->assertJsonPath('data.reference', 'FBNZUDHEJ')
            ->assertJsonPath('data.matched_by', 'origin_id')
            ->assertJsonPath('data.erp_number', '44398');
    }

    public function test_origin_id_of_another_shop_customer_is_not_linked(): void
    {
        $this->fakeUpstream(originId: '780002');
        $customer = $this->linkedCustomer();

        $this->mockPs()->shouldReceive('getOrderDetail')->once()->andReturnNull();

        $this->actingAs($this->agent(self::PERMS))
            ->getJson($this->url($customer))
            ->assertOk()
            ->assertJsonPath('state', 'ok')
            ->assertJsonPath('data.ps_order_id', null)
            ->assertJsonPath('reason', 'foreign');
    }

    public function test_falls_back_to_a_unique_same_day_same_amount_order(): void
    {
        $this->fakeUpstream(originId: null);
        $customer = $this->linkedCustomer();

        $ps = $this->mockPs();
        $ps->shouldNotReceive('getOrderDetail');
        $ps->shouldReceive('getCustomerOrders')->once()->andReturn(['data' => [
            ['id' => 780002, 'reference' => 'FBNZUDHEJ', 'total' => 120.98, 'created_at' => '2025-09-16 20:06:39'],
            ['id' => 780100, 'reference' => 'OTRODIAXX', 'total' => 120.98, 'created_at' => '2025-09-17 10:00:00'],
            ['id' => 780101, 'reference' => 'OTROIMPOR', 'total' => 15.00, 'created_at' => '2025-09-16 09:00:00'],
        ]]);

        $this->actingAs($this->agent(self::PERMS))
            ->getJson($this->url($customer))
            ->assertOk()
            ->assertJsonPath('data.ps_order_id', 780002)
            ->assertJsonPath('data.matched_by', 'date_amount');
    }

    public function test_fallback_with_two_candidates_does_not_guess(): void
    {
        $this->fakeUpstream(originId: null);
        $customer = $this->linkedCustomer();

        $this->mockPs()->shouldReceive('getCustomerOrders')->once()->andReturn(['data' => [
            ['id' => 780002, 'reference' => 'AAAAAAAAA', 'total' => 120.98, 'created_at' => '2025-09-16 20:06:39'],
            ['id' => 780003, 'reference' => 'BBBBBBBBB', 'total' => 120.98, 'created_at' => '2025-09-16 21:00:00'],
        ]]);

        $this->actingAs($this->agent(self::PERMS))
            ->getJson($this->url($customer))
            ->assertOk()
            ->assertJsonPath('data.ps_order_id', null)
            ->assertJsonPath('reason', 'no_match');
    }

    public function test_requires_shop_order_permission(): void
    {
        Http::fake();
        $customer = $this->linkedCustomer();

        $this->actingAs($this->agent(['helpdeskerp.view', 'helpdeskerp.orders.view']))
            ->getJson($this->url($customer))
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_order_of_another_erp_customer_is_404(): void
    {
        Http::fake(fn () => Http::response(['success' => false, 'error' => 'Order not found for this customer'], 404));
        $customer = $this->linkedCustomer();
        $this->mockPs()->shouldNotReceive('getOrderDetail');

        $this->actingAs($this->agent(self::PERMS))
            ->getJson($this->url($customer))
            ->assertNotFound()
            ->assertJsonPath('reason', 'not_found');
    }

    /* ── Fixtures ─────────────────────────────────────────────────────── */

    private function fakeUpstream(?string $originId): void
    {
        $xml = '<?xml version="1.0" encoding="utf-8"?><response><resource>'.
            '<idpedidocli>102138690</idpedidocli><fpedido>2025-09-16</fpedido><npedidocli>44398</npedidocli>'.
            '<identificadororigen>'.($originId ?? '').'</identificadororigen><total_con_impuestos>120.98</total_con_impuestos>'.
            '<cliente><idcliente>'.self::ERP.'</idcliente></cliente>'.
            '</resource></response>';

        Http::fake(function (Request $request) use ($xml) {
            $url = $request->url();

            if (str_starts_with($url, 'http://gestion.test/api-gestion/pedido-cliente/')) {
                return str_contains($url, 'idcliente='.self::ERP)
                    ? Http::response($xml, 200, ['Content-Type' => 'application/xml'])
                    : Http::response('Not Implemented', 501);
            }

            $path = (string) parse_url($url, PHP_URL_PATH);
            $sub = trim(substr($path, strlen('/api/erp/customer/'.self::ERP)), '/');

            return match ($sub) {
                'orders/'.self::ORDER => Http::response(['success' => true, 'data' => [
                    'id' => self::ORDER, 'order_id' => '102138690', 'number' => '44398', 'status' => true,
                    'origin' => ['id' => '4', 'description' => 'INTERNET'], 'date' => '2025-09-16 20:06:39',
                    'lines' => [['units' => 1, 'price' => 99.98, 'subtotal' => 99.98, 'tax_percent' => 21]],
                ]]),
                '' => Http::response(['success' => true, 'data' => ['id' => (int) self::ERP, 'code_internet' => '911230', 'available' => true]]),
                default => Http::response(['success' => false, 'error' => 'Acceso denegado a la tabla Oracle. Solicitar GRANT SELECT al DBA.'], 200),
            };
        });
    }

    private function mockPs(): Mockery\MockInterface
    {
        return $this->mock(PrestashopContextService::class);
    }

    private function url(Customer $customer): string
    {
        return '/panel/helpdesk/customers/'.$customer->id.'/erp/orders/'.self::ORDER.'/shop';
    }

    /**
     * @param  list<string>  $permissions
     */
    private function agent(array $permissions): User
    {
        $user = User::factory()->create();
        // helpdesk.customers.manage: atajo de CustomerPolicy::sharesInboxWith.
        $user->givePermissionTo([...$permissions, 'helpdesk.customers.manage']);

        return $user;
    }

    private function linkedCustomer(): Customer
    {
        $customer = Customer::factory()->create([
            'email' => Str::lower(Str::replace('-', '', (string) Str::uuid())).'@test.example',
        ]);
        $customer->linkExternalId('erp', self::ERP);
        $customer->linkExternalId('prestashop', '911230');

        return $customer->fresh();
    }
}
