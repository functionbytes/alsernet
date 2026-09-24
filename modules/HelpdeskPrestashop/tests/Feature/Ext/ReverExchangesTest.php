<?php

namespace Modules\HelpdeskPrestashop\Tests\Feature\Ext;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskPrestashop\Database\Seeders\HelpdeskPrestashopPermissionsSeeder;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Extensión "rever": cambios de producto gestionados por REVER, en lectura.
 * El bridge va simulado con Http::fake — nunca se llama a la tienda real ni a
 * la API de REVER.
 */
class ReverExchangesTest extends TestCase
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
        ]);

        $this->seed(HelpdeskPrestashopPermissionsSeeder::class);

        foreach (['helpdesk.customers.view', 'helpdesk.customers.manage'] as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
    }

    private function agent(string ...$extra): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(array_merge(['helpdesk.customers.view', 'helpdesk.customers.manage'], $extra));

        return $user;
    }

    private function customer(): Customer
    {
        return Customer::factory()->create(['email' => 'rever-'.uniqid().'@example.com']);
    }

    /** Forma real de un cambio tal como lo devuelve rever.order_exchanges. */
    private function exchange(): array
    {
        return [
            'exchange_order' => [
                'id' => 782167, 'reference' => 'CNEVNYBIZ', 'date' => '2025-09-28 13:52:02',
                'state_id' => 4, 'state_name' => 'Enviado', 'state_kind' => 'shipped',
                'payment' => 'Exchange by REVER', 'currency' => 'EUR', 'total_paid' => 0,
                'total_products' => 62.99, 'total_shipping' => 7.99, 'total_discounts' => 70.98,
                'lines' => [['order_detail_id' => 209501, 'product_id' => 53077, 'attribute_id' => 138743, 'name' => 'Pantalón Hart Ibero T-XHP (Talla: 46)', 'reference' => 'C307232-46', 'quantity' => 1, 'unit_price' => 62.99, 'total' => 62.99]],
                'tracking' => ['carrier' => 'Envío a la dirección seleccionada', 'number' => null, 'url' => null],
            ],
            'original_order' => [
                'id' => 780002, 'reference' => 'FBNZUDHEJ', 'state_id' => 75, 'state_name' => 'Devolución - Completada',
                'state_kind' => 'return_completed', 'payment' => 'Bizum / Devolución en REVER', 'refund_suffix' => true,
            ],
            'origin_reference' => 'FBNZUDHEJ',
            'process' => ['id' => 'retp_33KK3xRZZIyRQQw9bpgu105q9jj', 'started_at' => '2025-09-28 13:51:50', 'finished_at' => '2025-09-30 15:42:29', 'products_count' => 1, 'total_value' => 49.99, 'status' => 'finished'],
            'extra_payment' => null,
            'extra_payment_currency' => null,
            'voucher' => ['code' => '', 'name' => 'Exchange from FBNZUDHEJ return (by REVER)', 'value' => 70.98, 'free_shipping' => true],
            'returned' => ['source' => 'credit_slip', 'slip_id' => 114, 'number' => '000114', 'date' => '2025-09-30 15:45:30', 'amount' => 49.99, 'lines' => [['name' => 'Pantalón Hart Ibero T-XHP (Talla: 42)', 'quantity' => 1, 'amount' => 49.99]]],
            'return_history' => [],
        ];
    }

    public function test_order_exchanges_require_orders_view_permission(): void
    {
        Http::fake();

        $this->actingAs($this->agent())
            ->getJson(route('manager.helpdesk.ps.ext.rever.order', [$this->customer(), 780002]))
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_customer_exchanges_require_orders_view_permission(): void
    {
        Http::fake();

        $this->actingAs($this->agent())
            ->getJson(route('manager.helpdesk.ps.ext.rever.customer', [$this->customer()]))
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_order_exchanges_are_read_with_the_customer_lookup(): void
    {
        $customer = $this->customer();

        Http::fake([$this->apiUrl => Http::response(['ok' => true, 'data' => [
            'mode' => 'order', 'order_id' => 780002, 'reference' => 'FBNZUDHEJ', 'role' => 'original',
            'rever_enabled' => true, 'rever_states' => ['exchange' => 78, 'started' => 74, 'completed' => 75, 'partial' => 76, 'declined' => 77],
            'exchanges' => [$this->exchange()],
            'processes' => [],
        ]])]);

        $this->actingAs($this->agent('helpdeskprestashop.orders.view'))
            ->getJson(route('manager.helpdesk.ps.ext.rever.order', [$customer, 780002]))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.role', 'original')
            ->assertJsonPath('data.exchanges.0.exchange_order.reference', 'CNEVNYBIZ')
            ->assertJsonPath('data.exchanges.0.returned.lines.0.name', 'Pantalón Hart Ibero T-XHP (Talla: 42)');

        Http::assertSent(function (Request $request) use ($customer) {
            $data = $request->data();

            return ($data['action'] ?? null) === 'rever.order_exchanges'
                && ($data['order_id'] ?? null) === 780002
                && ($data['lookup']['email'] ?? null) === strtolower($customer->email)
                // Lectura: sin clave de idempotencia.
                && ! $request->hasHeader('X-Alsernet-Idempotency-Key');
        });
    }

    public function test_customer_exchanges_are_read_without_order_id(): void
    {
        Http::fake([$this->apiUrl => Http::response(['ok' => true, 'data' => [
            'mode' => 'customer', 'rever_enabled' => true, 'total' => 1,
            'exchanges' => [$this->exchange()],
        ]])]);

        $this->actingAs($this->agent('helpdeskprestashop.orders.view'))
            ->getJson(route('manager.helpdesk.ps.ext.rever.customer', [$this->customer()]))
            ->assertOk()
            ->assertJsonPath('data.mode', 'customer')
            ->assertJsonPath('data.total', 1)
            ->assertJsonCount(1, 'data.exchanges');

        Http::assertSent(function (Request $request) {
            $data = $request->data();

            return ($data['action'] ?? null) === 'rever.order_exchanges'
                && ! array_key_exists('order_id', $data);
        });
    }

    // Pedido de otro cliente: el puente responde 404 {ok:false}.
    public function test_order_of_another_customer_is_not_found(): void
    {
        Http::fake([$this->apiUrl => Http::response(['ok' => false, 'error' => 'unknown action or customer not found'], 404)]);

        $this->actingAs($this->agent('helpdeskprestashop.orders.view'))
            ->getJson(route('manager.helpdesk.ps.ext.rever.order', [$this->customer(), 999]))
            ->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_bridge_outage_is_reported_as_503(): void
    {
        Http::fake([$this->apiUrl => Http::response('boom', 500)]);

        $this->actingAs($this->agent('helpdeskprestashop.orders.view'))
            ->getJson(route('manager.helpdesk.ps.ext.rever.order', [$this->customer(), 780002]))
            ->assertStatus(503)
            ->assertJsonPath('success', false);
    }

    public function test_customer_without_email_nor_external_id_is_rejected_before_calling_the_bridge(): void
    {
        Http::fake();

        $customer = Customer::factory()->create(['email' => null]);

        $this->actingAs($this->agent('helpdeskprestashop.orders.view'))
            ->getJson(route('manager.helpdesk.ps.ext.rever.customer', [$customer]))
            ->assertStatus(422);

        Http::assertNothingSent();
    }
}
