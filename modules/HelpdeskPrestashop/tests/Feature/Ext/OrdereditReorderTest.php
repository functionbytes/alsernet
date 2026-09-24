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
 * Repetir pedido (pieza 11): GET/POST /customers/{customer}/ps/orders/{order}/reorder.
 * El bridge está simulado con Http::fake: nada llega a la tienda real.
 */
class OrdereditReorderTest extends TestCase
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

        foreach (['helpdesk.customers.view', 'helpdesk.customers.update', 'helpdesk.customers.manage'] as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
    }

    private function agent(string ...$extra): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(array_merge(['helpdesk.customers.view', 'helpdesk.customers.update', 'helpdesk.customers.manage'], $extra));

        return $user;
    }

    private function customer(): Customer
    {
        return Customer::factory()->create(['email' => 'repetir-'.uniqid().'@example.com']);
    }

    private function payload(): array
    {
        return ['lines' => [['order_detail_id' => 263068, 'quantity' => 2]]];
    }

    private function createdResponse(array $extra = []): array
    {
        return ['ok' => true, 'data' => array_merge([
            'created' => true,
            'cart_id' => 5550001,
            'source_order_id' => 816880,
            'source_reference' => 'BPGCSMHNY',
            'added' => [['order_detail_id' => 263068, 'product_id' => 43362, 'product_attribute_id' => 111127, 'name' => 'Tirantes Hart 45', 'quantity' => 2, 'unit_price' => 35.99]],
            'skipped' => [],
            'products_total' => 71.98,
            'has_address' => true,
        ], $extra)];
    }

    public function test_agent_without_reorder_permission_is_forbidden(): void
    {
        Http::fake();
        $customer = $this->customer();

        $this->actingAs($this->agent())
            ->getJson(route('manager.helpdesk.ps.ext.orderedit.reorder.preview', [$customer, 816880]))
            ->assertForbidden();

        $this->actingAs($this->agent())
            ->postJson(route('manager.helpdesk.ps.ext.orderedit.reorder.store', [$customer, 816880]), $this->payload())
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_preview_calls_the_bridge_with_the_customer_lookup(): void
    {
        $customer = $this->customer();

        Http::fake([$this->apiUrl => Http::response(['ok' => true, 'data' => [
            'order_id' => 816880,
            'reference' => 'BPGCSMHNY',
            'lines' => [['order_detail_id' => 263068, 'name' => 'Tirantes Hart 45', 'status' => 'ok', 'quantity' => 1, 'paid_unit' => 28.99, 'current_unit' => 35.99, 'price_changed' => true]],
            'products_total' => 35.99,
            'price_changes' => 1,
            'has_address' => true,
            'mirrored_carts' => 0,
        ]])]);

        $this->actingAs($this->agent('helpdeskprestashop.orders.reorder'))
            ->getJson(route('manager.helpdesk.ps.ext.orderedit.reorder.preview', [$customer, 816880]))
            ->assertOk()
            ->assertJsonPath('data.price_changes', 1)
            ->assertJsonPath('can_link', false);

        Http::assertSent(function (Request $request) use ($customer) {
            $data = $request->data();

            return ($data['action'] ?? null) === 'orderedit.reorder_preview'
                && ($data['order_id'] ?? null) === 816880
                && ($data['lookup']['email'] ?? null) === mb_strtolower($customer->email)
                && ! $request->hasHeader('X-Alsernet-Idempotency-Key');
        });
    }

    public function test_preview_of_an_order_that_is_not_the_customers_is_404(): void
    {
        Http::fake([$this->apiUrl => Http::response(['ok' => false, 'error' => 'unknown action or customer not found'])]);

        $this->actingAs($this->agent('helpdeskprestashop.orders.reorder'))
            ->getJson(route('manager.helpdesk.ps.ext.orderedit.reorder.preview', [$this->customer(), 999]))
            ->assertNotFound();
    }

    public function test_create_sends_a_write_with_idempotency_key_and_logs_activity(): void
    {
        $customer = $this->customer();
        Http::fake([$this->apiUrl => Http::response($this->createdResponse())]);

        $this->actingAs($this->agent('helpdeskprestashop.orders.reorder'))
            ->postJson(route('manager.helpdesk.ps.ext.orderedit.reorder.store', [$customer, 816880]), $this->payload())
            ->assertOk()
            ->assertJsonPath('data.cart_id', 5550001);

        Http::assertSent(function (Request $request) {
            $data = $request->data();

            return ($data['action'] ?? null) === 'orderedit.reorder_create'
                && ($data['order_id'] ?? null) === 816880
                && ($data['lines'][0]['order_detail_id'] ?? null) === 263068
                && ($data['lines'][0]['quantity'] ?? null) === 2
                && ($data['with_link'] ?? null) === false
                && $request->hasHeader('X-Alsernet-Idempotency-Key');
        });

        // Por el modelo (y su conexión) en vez de assertDatabaseHas, que
        // leería por la conexión por defecto y no vería la transacción ajena.
        $activityModel = config('activitylog.activity_model', Activity::class);
        $this->assertTrue($activityModel::query()
            ->where('log_name', 'helpdeskprestashop')
            ->where('description', 'ps.orderedit.reorder')
            ->where('subject_id', $customer->id)
            ->exists());
    }

    public function test_link_requires_its_own_permission(): void
    {
        Http::fake();

        $this->actingAs($this->agent('helpdeskprestashop.orders.reorder'))
            ->postJson(route('manager.helpdesk.ps.ext.orderedit.reorder.store', [$this->customer(), 816880]), $this->payload() + ['with_link' => true])
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_link_is_emailed_by_the_shop_and_never_reaches_the_browser(): void
    {
        // El bridge envía el enlace por correo y solo responde link_sent. Si
        // uno antiguo devolviera aún el enlace de auto-login, no debe salir.
        Http::fake([$this->apiUrl => Http::response($this->createdResponse([
            'link_sent' => true,
            'link' => 'https://tienda.test/pedido?recover_cart=5550001&token_cart=abc',
        ]))]);

        $this->actingAs($this->agent('helpdeskprestashop.orders.reorder', 'helpdeskprestashop.orders.reorder_link'))
            ->postJson(route('manager.helpdesk.ps.ext.orderedit.reorder.store', [$this->customer(), 816880]), $this->payload() + ['with_link' => true])
            ->assertOk()
            ->assertJsonPath('data.link_sent', true)
            ->assertJsonMissingPath('data.link');

        Http::assertSent(fn (Request $request) => ($request->data()['with_link'] ?? null) === true);
    }

    public function test_nothing_addable_is_reported_as_422_with_the_skipped_lines(): void
    {
        Http::fake([$this->apiUrl => Http::response(['ok' => true, 'data' => [
            'ok_semantic' => false,
            'created' => false,
            'error' => 'nothing_addable',
            'skipped' => [['order_detail_id' => 263068, 'name' => 'Tirantes Hart 45', 'reason' => 'discontinued']],
        ]])]);

        $this->actingAs($this->agent('helpdeskprestashop.orders.reorder'))
            ->postJson(route('manager.helpdesk.ps.ext.orderedit.reorder.store', [$this->customer(), 816880]), $this->payload())
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('skipped.0.reason', 'discontinued');
    }

    public function test_lines_are_validated_before_reaching_prestashop(): void
    {
        Http::fake();

        $this->actingAs($this->agent('helpdeskprestashop.orders.reorder'))
            ->postJson(route('manager.helpdesk.ps.ext.orderedit.reorder.store', [$this->customer(), 816880]), ['lines' => [['order_detail_id' => 1, 'quantity' => 0]]])
            ->assertStatus(422)
            ->assertJsonValidationErrors('lines.0.quantity');

        Http::assertNothingSent();
    }

    public function test_upstream_failure_is_503(): void
    {
        Http::fake([$this->apiUrl => Http::response('', 500)]);

        $this->actingAs($this->agent('helpdeskprestashop.orders.reorder'))
            ->postJson(route('manager.helpdesk.ps.ext.orderedit.reorder.store', [$this->customer(), 816880]), $this->payload())
            ->assertStatus(503);
    }
}
