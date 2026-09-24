<?php

namespace Modules\HelpdeskPrestashop\Tests\Feature\Ext;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskPrestashop\Database\Seeders\HelpdeskPrestashopPermissionsSeeder;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Extensión "catalog": ficha de stock/plazo/precio (piezas 16, 25, 26),
 * comparación (10) y aviso de vuelta a stock. El puente se simula con
 * Http::fake; aquí se comprueba permiso, propiedad del cliente, lo que se
 * envía al puente y cómo se traducen sus respuestas.
 */
class CatalogTest extends TestCase
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
        return Customer::factory()->create(['email' => 'catalog-'.uniqid().'@example.com']);
    }

    private function sheetData(array $overrides = []): array
    {
        return array_replace_recursive([
            'product' => ['id' => 52959, 'name' => 'Jig Head Vega', 'reference' => 'P104201-3', 'active' => true, 'has_combinations' => false, 'product_attribute_id' => 0],
            'stock' => [
                'web' => 0,
                'locations' => [
                    ['key' => 'pocomaco', 'units' => 0],
                    ['key' => 'ddleon', 'units' => 2],
                ],
                'total' => 2,
                'has_breakdown' => true,
                'supplier_days' => null,
            ],
            'availability' => ['in_stock' => false, 'delivery_code' => null, 'delivery_text' => null, 'no48h' => false, 'restock_date' => null, 'alerts_enabled' => true, 'alert_subscribed' => false],
            'pricing' => [
                'product_attribute_id' => 0,
                'currency' => 'EUR',
                'customer' => ['group_id' => 3, 'group_name' => 'Cliente', 'country_id' => 6, 'country_name' => 'España', 'price_with_tax' => 8.99],
                'public' => ['group_id' => 1, 'group_name' => 'Visitante', 'country_id' => 6, 'country_name' => 'España', 'price_with_tax' => 8.99],
                'tiers' => [['from_quantity' => 2, 'price_with_tax' => 8.34]],
            ],
        ], $overrides);
    }

    public function test_sheet_sends_customer_lookup_and_labels_locations(): void
    {
        Http::fake([$this->apiUrl => Http::response(['ok' => true, 'data' => $this->sheetData()])]);
        $customer = $this->customer();

        $this->actingAs($this->agent('helpdeskprestashop.catalog.stock_alert'))
            ->getJson(route('manager.helpdesk.ps.ext.catalog.sheet', [$customer, 52959]).'?product_attribute_id=0')
            ->assertOk()
            ->assertJsonPath('data.stock.locations.1.label', 'Tienda Diego de León · Madrid')
            ->assertJsonPath('data.pricing.tiers.0.from_quantity', 2)
            ->assertJsonPath('can.stock_alert', true);

        Http::assertSent(function (Request $request) use ($customer) {
            $data = $request->data();

            return ($data['action'] ?? null) === 'catalog.product_sheet'
                && ($data['product_id'] ?? null) === 52959
                && ($data['lookup']['email'] ?? null) === strtolower($customer->email)
                && ! $request->hasHeader('X-Alsernet-Idempotency-Key');
        });
    }

    public function test_sheet_is_cached_per_customer_and_product(): void
    {
        Http::fake([$this->apiUrl => Http::response(['ok' => true, 'data' => $this->sheetData()])]);
        $customer = $this->customer();
        $agent = $this->agent();

        $this->actingAs($agent)->getJson(route('manager.helpdesk.ps.ext.catalog.sheet', [$customer, 52959]))->assertOk();
        $this->actingAs($agent)->getJson(route('manager.helpdesk.ps.ext.catalog.sheet', [$customer, 52959]))->assertOk();

        Http::assertSentCount(1);
    }

    public function test_sheet_reports_missing_product_as_404(): void
    {
        // Forma real del puente: producto inexistente = 200 con product=null
        // (nunca un 404, que el panel contaría como caída).
        Http::fake([$this->apiUrl => Http::response(['ok' => true, 'data' => ['product' => null]])]);

        $this->actingAs($this->agent())
            ->getJson(route('manager.helpdesk.ps.ext.catalog.sheet', [$this->customer(), 999999]))
            ->assertNotFound();
    }

    public function test_sheet_reports_bridge_down_as_503(): void
    {
        Http::fake([$this->apiUrl => Http::response('boom', 500)]);

        $this->actingAs($this->agent())
            ->getJson(route('manager.helpdesk.ps.ext.catalog.sheet', [$this->customer(), 52959]))
            ->assertStatus(503)
            ->assertJsonPath('success', false);
    }

    public function test_sheet_requires_access_to_the_customer(): void
    {
        Http::fake();

        $this->actingAs(User::factory()->create())
            ->getJson(route('manager.helpdesk.ps.ext.catalog.sheet', [$this->customer(), 52959]))
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_compare_validates_between_two_and_three_distinct_products(): void
    {
        Http::fake();
        $agent = $this->agent();
        $customer = $this->customer();

        $this->actingAs($agent)
            ->postJson(route('manager.helpdesk.ps.ext.catalog.compare', $customer), ['product_ids' => [1]])
            ->assertStatus(422)
            ->assertJsonValidationErrors('product_ids');

        $this->actingAs($agent)
            ->postJson(route('manager.helpdesk.ps.ext.catalog.compare', $customer), ['product_ids' => [1, 2, 3, 4]])
            ->assertStatus(422)
            ->assertJsonValidationErrors('product_ids');

        $this->actingAs($agent)
            ->postJson(route('manager.helpdesk.ps.ext.catalog.compare', $customer), ['product_ids' => [5, 5]])
            ->assertStatus(422);

        Http::assertNothingSent();
    }

    public function test_compare_keeps_requested_order_and_reports_missing_products(): void
    {
        Http::fake([$this->apiUrl => Http::response(['ok' => true, 'data' => ['items' => [
            ['id' => 20, 'name' => 'B', 'price_with_tax' => 10.0, 'variants' => []],
            ['id' => 10, 'name' => 'A', 'price_with_tax' => 12.0, 'variants' => []],
        ]]])]);

        $this->actingAs($this->agent())
            ->postJson(route('manager.helpdesk.ps.ext.catalog.compare', $this->customer()), ['product_ids' => [10, 20, 30]])
            ->assertOk()
            ->assertJsonPath('items.0.id', 10)
            ->assertJsonPath('items.1.id', 20)
            ->assertJsonPath('missing', [30]);

        Http::assertSent(fn (Request $request) => ($request->data()['action'] ?? null) === 'catalog.product_compare'
            && ($request->data()['product_ids'] ?? null) === [10, 20, 30]);
    }

    public function test_stock_alert_requires_its_own_permission(): void
    {
        Http::fake();

        $this->actingAs($this->agent())
            ->postJson(route('manager.helpdesk.ps.ext.catalog.stock_alert', [$this->customer(), 52959]), ['product_attribute_id' => 0])
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_restricted_agent_role_cannot_subscribe_alerts(): void
    {
        $ext = (array) config('helpdeskprestashop.ext.catalog.role_permissions', []);

        $this->assertArrayNotHasKey('helpdesk-agent-restricted', $ext);
        $this->assertContains('catalog.stock_alert', (array) config('helpdeskprestashop.ext_write_actions', []));
    }

    public function test_stock_alert_is_sent_as_write_with_idempotency_key_and_logged(): void
    {
        Http::fake([$this->apiUrl => Http::response(['ok' => true, 'data' => ['subscribed' => true, 'already' => false]])]);
        $customer = $this->customer();
        $agent = $this->agent('helpdeskprestashop.catalog.stock_alert');

        $this->actingAs($agent)
            ->postJson(route('manager.helpdesk.ps.ext.catalog.stock_alert', [$customer, 52959]), ['product_attribute_id' => 188, 'conversation_id' => 7])
            ->assertOk()
            ->assertJsonPath('data.subscribed', true);

        Http::assertSent(function (Request $request) use ($customer) {
            $data = $request->data();

            return ($data['action'] ?? null) === 'catalog.stock_alert'
                && ($data['product_id'] ?? null) === 52959
                && ($data['product_attribute_id'] ?? null) === 188
                && ($data['lookup']['email'] ?? null) === strtolower($customer->email)
                && $request->hasHeader('X-Alsernet-Idempotency-Key');
        });

        $this->assertTrue(Activity::query()
            ->where('log_name', 'helpdeskprestashop')
            ->where('description', 'ps.catalog.stock_alert')
            ->where('causer_id', $agent->id)
            ->where('subject_id', $customer->id)
            ->exists());
    }

    public function test_stock_alert_rejected_by_the_bridge_is_a_422(): void
    {
        Http::fake([$this->apiUrl => Http::response(['ok' => true, 'data' => ['ok_semantic' => false, 'subscribed' => false, 'error' => 'in_stock']])]);

        $this->actingAs($this->agent('helpdeskprestashop.catalog.stock_alert'))
            ->postJson(route('manager.helpdesk.ps.ext.catalog.stock_alert', [$this->customer(), 52959]))
            ->assertStatus(422)
            ->assertJsonPath('error', 'in_stock');
    }

    public function test_stock_alert_without_combination_on_a_product_with_variants_is_a_422(): void
    {
        Http::fake([$this->apiUrl => Http::response(['ok' => true, 'data' => ['ok_semantic' => false, 'subscribed' => false, 'error' => 'combination_required']])]);

        $this->actingAs($this->agent('helpdeskprestashop.catalog.stock_alert'))
            ->postJson(route('manager.helpdesk.ps.ext.catalog.stock_alert', [$this->customer(), 52959]), ['product_attribute_id' => 0])
            ->assertStatus(422)
            ->assertJsonPath('error', 'combination_required');
    }

    public function test_stock_alert_for_a_customer_unknown_to_the_shop_is_a_404(): void
    {
        Http::fake([$this->apiUrl => Http::response(['ok' => true, 'data' => ['ok_semantic' => false, 'subscribed' => false, 'error' => 'customer_not_found']])]);

        $this->actingAs($this->agent('helpdeskprestashop.catalog.stock_alert'))
            ->postJson(route('manager.helpdesk.ps.ext.catalog.stock_alert', [$this->customer(), 52959]), ['product_attribute_id' => 188])
            ->assertNotFound()
            ->assertJsonPath('error', 'customer_not_found');
    }

    public function test_stock_alert_for_customer_without_shop_identity_never_reaches_prestashop(): void
    {
        Http::fake();
        $customer = Customer::factory()->create(['email' => null]);

        $this->actingAs($this->agent('helpdeskprestashop.catalog.stock_alert'))
            ->postJson(route('manager.helpdesk.ps.ext.catalog.stock_alert', [$customer, 52959]))
            ->assertStatus(422);

        Http::assertNothingSent();
    }
}
