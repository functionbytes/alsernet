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
 * Extensión "cartpay": cobro del pedido pendiente (pieza 02) y convertir /
 * vaciar el carrito en vivo (pieza 32). El puente está simulado con
 * Http::fake: nada llega a la tienda real.
 */
class CartpayActionsTest extends TestCase
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
        return Customer::factory()->create(['email' => 'cartpay-'.uniqid().'@example.com']);
    }

    // ── Pieza 02 ──────────────────────────────────────────────

    public function test_order_payment_returns_bank_wire_details(): void
    {
        Http::fake([$this->apiUrl => Http::response(['ok' => true, 'data' => [
            'order_id' => 732179, 'reference' => 'BPFJWZRCS', 'module' => 'ps_wirepayment',
            'payment' => 'Pagos por transferencia bancaria', 'state_id' => 10,
            'state_name' => 'En espera de pago por transferencia bancaria', 'is_open' => true, 'is_paid' => false,
            'total' => 317.99, 'paid' => 0, 'pending' => 317.99, 'currency' => 'EUR',
            'bank_wire' => ['owner' => 'Titular', 'details' => 'ES00 0000', 'address' => 'Banco', 'concept' => 'BPFJWZRCS'],
            'payment_link' => null, 'invoice' => ['available' => false, 'reason' => 'erp_issues_invoices'],
        ]])]);

        $this->actingAs($this->agent('helpdeskprestashop.orders.view'))
            ->getJson(route('manager.helpdesk.ps.ext.cartpay.order-payment', [$this->customer(), 732179]))
            ->assertOk()
            ->assertJsonPath('data.bank_wire.concept', 'BPFJWZRCS')
            ->assertJsonPath('data.payment_link', null);

        Http::assertSent(fn (Request $r) => ($r->data()['action'] ?? null) === 'cartpay.order_payment'
            && ($r->data()['order_id'] ?? null) === 732179
            && ! $r->hasHeader('X-Alsernet-Idempotency-Key'));
    }

    public function test_order_payment_requires_orders_view(): void
    {
        Http::fake();

        $this->actingAs($this->agent())
            ->getJson(route('manager.helpdesk.ps.ext.cartpay.order-payment', [$this->customer(), 732179]))
            ->assertForbidden();

        Http::assertNothingSent();
    }

    // ── Pieza 32 · vista previa ───────────────────────────────

    public function test_preview_exposes_what_the_agent_can_do_and_translates_blocking(): void
    {
        Http::fake([$this->apiUrl => Http::response(['ok' => true, 'data' => [
            'cart_id' => 2146979, 'items' => 5, 'total' => 1426.96, 'blocking' => ['no_invoice_address'],
            'states' => [['key' => 'bankwire', 'id' => 10, 'name' => 'En espera de pago por transferencia bancaria', 'paid' => false]],
        ]])]);

        $this->actingAs($this->agent('helpdeskprestashop.cartpay.convert'))
            ->getJson(route('manager.helpdesk.ps.ext.cartpay.preview', [$this->customer(), 2146979]))
            ->assertOk()
            ->assertJsonPath('data.can.convert', true)
            ->assertJsonPath('data.can.convert_paid', false)
            ->assertJsonPath('data.can.empty', false)
            ->assertJsonPath('data.blocking_messages.0', 'El carrito no tiene dirección de facturación del cliente.');
    }

    public function test_preview_without_any_cartpay_permission_is_forbidden(): void
    {
        Http::fake();

        $this->actingAs($this->agent('helpdeskprestashop.carts.manage'))
            ->getJson(route('manager.helpdesk.ps.ext.cartpay.preview', [$this->customer(), 2146979]))
            ->assertForbidden();

        Http::assertNothingSent();
    }

    // ── Pieza 32 · convertir ──────────────────────────────────

    public function test_convert_without_permission_is_forbidden(): void
    {
        Http::fake();

        $this->actingAs($this->agent('helpdeskprestashop.carts.manage'))
            ->postJson(route('manager.helpdesk.ps.ext.cartpay.convert', [$this->customer(), 2146979]), ['state' => 'bankwire'])
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_paid_state_needs_convert_paid_and_never_reaches_prestashop(): void
    {
        Http::fake();

        $this->actingAs($this->agent('helpdeskprestashop.cartpay.convert'))
            ->postJson(route('manager.helpdesk.ps.ext.cartpay.convert', [$this->customer(), 2146979]), ['state' => 'payment'])
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_convert_needs_update_access_to_the_customer(): void
    {
        Http::fake();

        // Tiene el permiso de convertir pero no puede modificar clientes
        // (CustomerPolicy::update): el pedido no se crea.
        $user = User::factory()->create();
        $user->givePermissionTo(['helpdesk.customers.view', 'helpdesk.customers.manage', 'helpdeskprestashop.cartpay.convert']);

        $this->actingAs($user)
            ->postJson(route('manager.helpdesk.ps.ext.cartpay.convert', [$this->customer(), 2146979]), ['state' => 'bankwire'])
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_order_created_with_errors_is_reported_as_created_with_warning(): void
    {
        Http::fake([$this->apiUrl => Http::response(['ok' => true, 'data' => [
            'created' => true, 'order_id' => 900002, 'reference' => 'JKLMNOPQR', 'state_id' => 10,
            'state_name' => 'En espera de pago por transferencia bancaria', 'state_error' => false,
            'completed_with_errors' => true, 'total' => 120.5, 'payment' => 'Pagos por transferencia bancaria',
        ]])]);

        $this->actingAs($this->agent('helpdeskprestashop.cartpay.convert'))
            ->postJson(route('manager.helpdesk.ps.ext.cartpay.convert', [$this->customer(), 5822]), ['state' => 'bankwire'])
            ->assertOk()
            ->assertJsonPath('data.order_id', 900002)
            ->assertJsonPath('warning', 'El pedido se ha creado, pero PrestaShop registró un error al terminar (correo o módulos). Revísalo antes de avisar al cliente.');
    }

    public function test_client_idempotency_key_is_scoped_before_reaching_the_bridge(): void
    {
        Http::fake([$this->apiUrl => Http::response(['ok' => true, 'data' => [
            'emptied' => true, 'cart_id' => 5821, 'removed_products' => 1, 'removed_vouchers' => 0, 'remaining' => 0,
        ]])]);

        $user = $this->agent('helpdeskprestashop.cartpay.empty');
        $customer = $this->customer();

        $this->actingAs($user)
            ->withHeader('Idempotency-Key', 'cartpay-intento-1')
            ->postJson(route('manager.helpdesk.ps.ext.cartpay.empty', [$customer, 5821]))
            ->assertOk();

        // La clave del navegador llega atada a usuario + cliente + carrito + acción.
        $expected = sha1(implode(':', [$user->getAuthIdentifier(), $customer->id, 5821, 'cartpay.empty', 'cartpay-intento-1']));

        Http::assertSent(fn (Request $r) => $r->header('X-Alsernet-Idempotency-Key') === [$expected]);
    }

    public function test_unknown_state_is_rejected(): void
    {
        Http::fake();

        $this->actingAs($this->agent('helpdeskprestashop.cartpay.convert', 'helpdeskprestashop.cartpay.convert_paid'))
            ->postJson(route('manager.helpdesk.ps.ext.cartpay.convert', [$this->customer(), 2146979]), ['state' => 'shipped'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('state');

        Http::assertNothingSent();
    }

    public function test_convert_creates_order_with_idempotency_and_logs_activity(): void
    {
        Http::fake([$this->apiUrl => Http::response(['ok' => true, 'data' => [
            'created' => true, 'order_id' => 900001, 'reference' => 'ABCDEFGHI', 'state_id' => 10,
            'state_name' => 'En espera de pago por transferencia bancaria', 'total' => 755.9,
            'payment' => 'Pagos por transferencia bancaria',
        ]])]);

        $customer = $this->customer();

        $this->actingAs($this->agent('helpdeskprestashop.cartpay.convert'))
            ->postJson(route('manager.helpdesk.ps.ext.cartpay.convert', [$customer, 5821]), ['state' => 'bankwire'])
            ->assertOk()
            ->assertJsonPath('data.order_id', 900001);

        Http::assertSent(fn (Request $r) => ($r->data()['action'] ?? null) === 'cartpay.convert'
            && ($r->data()['cart_id'] ?? null) === 5821
            && ($r->data()['state'] ?? null) === 'bankwire'
            && ($r->data()['lookup']['email'] ?? null) === $customer->email
            && $r->hasHeader('X-Alsernet-Idempotency-Key'));

        $this->assertTrue(Activity::query()
            ->where('log_name', 'helpdeskprestashop')
            ->where('description', 'ps.cart.converted')
            ->where('subject_id', $customer->id)
            ->exists());
    }

    public function test_convert_reports_bridge_blocking_as_422(): void
    {
        Http::fake([$this->apiUrl => Http::response(['ok' => true, 'data' => [
            'created' => false, 'ok_semantic' => false, 'error' => 'blocked', 'blocking' => ['out_of_stock'],
        ]])]);

        $this->actingAs($this->agent('helpdeskprestashop.cartpay.convert'))
            ->postJson(route('manager.helpdesk.ps.ext.cartpay.convert', [$this->customer(), 5821]), ['state' => 'bankwire'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Hay productos sin stock suficiente.');
    }

    public function test_bridge_down_on_convert_is_503(): void
    {
        Http::fake([$this->apiUrl => Http::response('boom', 500)]);

        $this->actingAs($this->agent('helpdeskprestashop.cartpay.convert'))
            ->postJson(route('manager.helpdesk.ps.ext.cartpay.convert', [$this->customer(), 5821]), ['state' => 'bankwire'])
            ->assertStatus(503);
    }

    // ── Pieza 32 · vaciar ─────────────────────────────────────

    public function test_empty_without_permission_is_forbidden(): void
    {
        Http::fake();

        $this->actingAs($this->agent('helpdeskprestashop.cartpay.convert'))
            ->postJson(route('manager.helpdesk.ps.ext.cartpay.empty', [$this->customer(), 5821]))
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_empty_clears_cart_and_logs_activity(): void
    {
        Http::fake([$this->apiUrl => Http::response(['ok' => true, 'data' => [
            'emptied' => true, 'cart_id' => 5821, 'removed_products' => 3, 'removed_vouchers' => 1, 'remaining' => 0,
        ]])]);

        $customer = $this->customer();

        $this->actingAs($this->agent('helpdeskprestashop.cartpay.empty'))
            ->postJson(route('manager.helpdesk.ps.ext.cartpay.empty', [$customer, 5821]))
            ->assertOk()
            ->assertJsonPath('data.removed_products', 3);

        Http::assertSent(fn (Request $r) => ($r->data()['action'] ?? null) === 'cartpay.empty'
            && ($r->data()['cart_id'] ?? null) === 5821
            && $r->hasHeader('X-Alsernet-Idempotency-Key'));

        $this->assertTrue(Activity::query()
            ->where('log_name', 'helpdeskprestashop')
            ->where('description', 'ps.cart.emptied')
            ->where('subject_id', $customer->id)
            ->exists());
    }

    public function test_empty_on_ordered_cart_is_422(): void
    {
        Http::fake([$this->apiUrl => Http::response(['ok' => true, 'data' => [
            'emptied' => false, 'ok_semantic' => false, 'error' => 'already_ordered',
        ]])]);

        $this->actingAs($this->agent('helpdeskprestashop.cartpay.empty'))
            ->postJson(route('manager.helpdesk.ps.ext.cartpay.empty', [$this->customer(), 5821]))
            ->assertStatus(422)
            ->assertJsonPath('message', 'Este carrito ya es un pedido: no se vacía.');
    }

    public function test_extension_config_registers_write_actions_and_restricted_agent_gets_nothing(): void
    {
        $this->assertContains('cartpay.convert', config('helpdeskprestashop.ext_write_actions'));
        $this->assertContains('cartpay.empty', config('helpdeskprestashop.ext_write_actions'));
        $this->assertArrayNotHasKey('helpdesk-agent-restricted', config('helpdeskprestashop.ext.cartpay.role_permissions'));
    }
}
