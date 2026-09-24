<?php

namespace Modules\HelpdeskPrestashop\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskPrestashop\Database\Seeders\HelpdeskPrestashopPermissionsSeeder;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * GET /customers/{customer}/ps/orders — la única llamada del tab "Tienda".
 *
 * Tiene que distinguir "el puente no responde" (503, el panel lo dice) de
 * "el cliente no existe en PrestaShop" (200 + found=false, el panel ofrece
 * "Buscar y vincular"). Antes las dos cosas devolvían 200 con listas vacías
 * y el panel pintaba "Sin pedidos · datos actualizados".
 */
class PsStoreContextEndpointTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private string $apiUrl = 'https://ps.test/modules/alsernetbridge/api.php';

    protected function setUp(): void
    {
        parent::setUp();

        // Caché en memoria: el circuit breaker y el contexto cacheado NO deben
        // tocar el Redis real de la app de desarrollo.
        config([
            'cache.default' => 'array',
            'helpdeskprestashop.api_url' => $this->apiUrl,
            'helpdeskprestashop.webhook_secret' => 'test-secret-for-hmac',
            'helpdeskprestashop.cache_ttl' => 300,
            'helpdeskprestashop.http_timeout' => 5,
        ]);

        $this->seed(HelpdeskPrestashopPermissionsSeeder::class);

        foreach (['helpdesk.customers.view', 'helpdesk.customers.manage'] as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
    }

    private function agent(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['helpdesk.customers.view', 'helpdesk.customers.manage']);

        return $user;
    }

    private function customer(string $email): Customer
    {
        return Customer::factory()->create(['email' => $email]);
    }

    private function bridgeContext(array $customer, array $extra = []): array
    {
        return [
            'ok' => true,
            'data' => array_merge([
                'customer' => $customer,
                'orders' => [],
                'carts' => [],
                'addresses' => [['id' => 7, 'alias' => 'Casa']],
                'returns' => [['id' => 1, 'state_name' => 'Esperando paquete']],
                'vouchers' => [],
                'refunds' => [],
                'messages' => [],
                'wishlist' => [],
            ], $extra),
        ];
    }

    public function test_found_customer_returns_the_whole_context_in_one_call(): void
    {
        Http::fake([$this->apiUrl => Http::response($this->bridgeContext([
            'found' => true, 'id' => 124515, 'firstname' => 'Gabriel', 'lastname' => 'Morales', 'orders_count' => 0,
        ]))]);

        $customer = $this->customer('store-found-'.uniqid().'@example.com');

        $this->actingAs($this->agent())
            ->getJson(route('manager.helpdesk.customers.ps.orders', $customer))
            ->assertOk()
            ->assertJsonPath('bridge', 'ok')
            ->assertJsonPath('customer.found', true)
            ->assertJsonPath('addresses.0.alias', 'Casa')
            ->assertJsonPath('returns.0.state_name', 'Esperando paquete')
            ->assertJsonStructure(['fetched_at', 'orders', 'carts', 'vouchers', 'refunds', 'messages', 'wishlist']);

        Http::assertSentCount(1);
    }

    public function test_unknown_customer_is_not_reported_as_a_bridge_failure(): void
    {
        Http::fake([$this->apiUrl => Http::response($this->bridgeContext(['found' => false]))]);

        $customer = $this->customer('store-unknown-'.uniqid().'@example.com');

        $this->actingAs($this->agent())
            ->getJson(route('manager.helpdesk.customers.ps.orders', $customer))
            ->assertOk()
            ->assertJsonPath('bridge', 'ok')
            ->assertJsonPath('customer.found', false);
    }

    public function test_bridge_down_returns_503_instead_of_an_empty_context(): void
    {
        Http::fake([$this->apiUrl => Http::response('upstream error', 500)]);

        $customer = $this->customer('store-down-'.uniqid().'@example.com');

        $this->actingAs($this->agent())
            ->getJson(route('manager.helpdesk.customers.ps.orders', $customer))
            ->assertStatus(503)
            ->assertJsonPath('bridge', 'down')
            ->assertJsonPath('stale', false);
    }

    public function test_bridge_down_on_refresh_serves_the_previous_cache_marked_as_stale(): void
    {
        $customer = $this->customer('store-stale-'.uniqid().'@example.com');
        $agent = $this->agent();

        Http::fake([$this->apiUrl => Http::sequence()
            ->push($this->bridgeContext(['found' => true, 'id' => 55, 'orders_count' => 0]))
            ->push('upstream error', 500)
            ->push('upstream error', 500)]);

        $this->actingAs($agent)->getJson(route('manager.helpdesk.customers.ps.orders', $customer))->assertOk();

        $this->actingAs($agent)
            ->getJson(route('manager.helpdesk.customers.ps.orders', $customer).'?fresh=1')
            ->assertStatus(503)
            ->assertJsonPath('stale', true)
            ->assertJsonPath('customer.id', 55);
    }

    public function test_linked_external_id_is_sent_to_the_bridge(): void
    {
        // Id ficticio: (platform, external_id) es único en la tabla real y los
        // ids de staging ya están vinculados a contactos de desarrollo.
        $psId = random_int(900000000, 999999999);
        Http::fake([$this->apiUrl => Http::response($this->bridgeContext(['found' => true, 'id' => $psId, 'orders_count' => 0]))]);

        // Email del contacto distinto del de la tienda: sin el id vinculado el
        // bridge no encontraría al cliente.
        $customer = $this->customer('store-linked-'.uniqid().'@example.invalid');
        $customer->linkExternalId('prestashop', (string) $psId, ['linked_via' => 'manual']);

        $this->actingAs($this->agent())
            ->getJson(route('manager.helpdesk.customers.ps.orders', $customer))
            ->assertOk();

        Http::assertSent(function (Request $request) use ($psId) {
            return ($request->data()['lookup']['external_id'] ?? null) === $psId;
        });
    }
}
