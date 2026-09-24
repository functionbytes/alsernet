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
 * Reembolso parcial (pieza 08). El puente se simula con Http::fake: nunca se
 * toca la tienda. Por encima del límite del agente no llega ninguna escritura
 * a PrestaShop; el límite viaja además al puente como max_amount_cents.
 */
class RefundsOrderTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private string $apiUrl = 'https://ps.test/modules/alsernetbridge/api.php';

    /** @var array<int, string> acciones que llegaron al puente simulado */
    private array $actions = [];

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'cache.default' => 'array',
            'helpdeskprestashop.api_url' => $this->apiUrl,
            'helpdeskprestashop.webhook_secret' => 'test-secret-for-hmac',
            'helpdeskprestashop.ext.refunds.agent_limit' => 50,
            'helpdeskprestashop.ext.refunds.approver_limit' => 500,
        ]);

        $this->seed(HelpdeskPrestashopPermissionsSeeder::class);

        foreach (['helpdesk.customers.view', 'helpdesk.customers.update', 'helpdesk.customers.manage'] as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
    }

    private function agent(string ...$extra): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(array_merge(
            ['helpdesk.customers.view', 'helpdesk.customers.update', 'helpdesk.customers.manage', 'helpdeskprestashop.orders.view'],
            $extra,
        ));

        return $user;
    }

    private function customer(): Customer
    {
        return Customer::factory()->create(['email' => 'reembolso-'.uniqid().'@example.com']);
    }

    /** Pedido pagado con dos líneas (40 € ×2 y 129 € ×1) y 5,90 € de envío. */
    private function refundable(): array
    {
        return [
            'order_id' => 900001,
            'reference' => 'ABCDEFGHI',
            'paid' => true,
            'delivered' => true,
            'tax_included' => true,
            'total_paid' => 214.90,
            'already_refunded' => 0,
            'shipping_refundable' => 5.90,
            'lines' => [
                ['order_detail_id' => 11, 'name' => 'Brocas SDS-plus', 'quantity' => 2, 'refundable' => 2, 'unit_price' => 40.0, 'unit_price_tax_incl' => 40.0],
                ['order_detail_id' => 12, 'name' => 'Taladro GSB 18V-55', 'quantity' => 1, 'refundable' => 1, 'unit_price' => 129.0, 'unit_price_tax_incl' => 129.0],
            ],
        ];
    }

    /**
     * Puente simulado: responde según la acción y apunta cada llamada.
     */
    private function fakeBridge(?array $writeResult = null): void
    {
        $this->actions = [];

        Http::fake(function (Request $request) use ($writeResult) {
            $action = $request->data()['action'] ?? '';
            $this->actions[] = $action;

            return match ($action) {
                'refunds.order_refundable' => Http::response(['ok' => true, 'data' => $this->refundable()]),
                'refunds.issue_partial' => Http::response(['ok' => true, 'data' => $writeResult ?? [
                    'refunded' => true, 'order_id' => 900001, 'order_slip_id' => 77, 'amount' => 40.0,
                    'shipping' => 0.0, 'destination' => 'payment', 'voucher' => null,
                ]]),
                default => Http::response(['ok' => false, 'error' => 'unknown'], 404),
            };
        });
    }

    private function url(Customer $customer): string
    {
        return route('manager.helpdesk.ps.ext.refunds.orders.store', [$customer, 900001]);
    }

    public function test_agent_without_refund_permission_is_forbidden(): void
    {
        Http::fake();

        $this->actingAs($this->agent())
            ->postJson($this->url($this->customer()), ['lines' => [['order_detail_id' => 11, 'quantity' => 1]], 'destination' => 'payment'])
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_agent_without_access_to_the_customer_is_forbidden(): void
    {
        Http::fake();

        $user = User::factory()->create();
        $user->givePermissionTo(['helpdeskprestashop.refunds.issue', 'helpdesk.customers.update']);

        $this->actingAs($user)
            ->postJson($this->url($this->customer()), ['lines' => [['order_detail_id' => 11, 'quantity' => 1]], 'destination' => 'payment'])
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_nothing_selected_is_a_validation_error(): void
    {
        Http::fake();

        $this->actingAs($this->agent('helpdeskprestashop.refunds.issue'))
            ->postJson($this->url($this->customer()), ['lines' => [], 'destination' => 'payment'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('lines');

        Http::assertNothingSent();
    }

    public function test_amount_over_agent_limit_needs_approval_and_never_writes(): void
    {
        $this->fakeBridge();

        // 129 € > 50 € de límite de agente.
        $this->actingAs($this->agent('helpdeskprestashop.refunds.issue'))
            ->postJson($this->url($this->customer()), ['lines' => [['order_detail_id' => 12, 'quantity' => 1]], 'destination' => 'payment'])
            ->assertStatus(422)
            ->assertJsonPath('needs_approval', true)
            ->assertJsonPath('limit', 50);

        $this->assertSame(['refunds.order_refundable'], $this->actions);
    }

    public function test_quantity_above_refundable_is_rejected_before_writing(): void
    {
        $this->fakeBridge();

        $this->actingAs($this->agent('helpdeskprestashop.refunds.approve'))
            ->postJson($this->url($this->customer()), ['lines' => [['order_detail_id' => 11, 'quantity' => 3]], 'destination' => 'payment'])
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertNotContains('refunds.issue_partial', $this->actions);
    }

    public function test_line_of_another_order_is_rejected_before_writing(): void
    {
        $this->fakeBridge();

        $this->actingAs($this->agent('helpdeskprestashop.refunds.approve'))
            ->postJson($this->url($this->customer()), ['lines' => [['order_detail_id' => 999, 'quantity' => 1]], 'destination' => 'payment'])
            ->assertStatus(422);

        $this->assertNotContains('refunds.issue_partial', $this->actions);
    }

    public function test_approver_issues_refund_with_limit_and_idempotency_key(): void
    {
        $this->fakeBridge();

        $this->actingAs($this->agent('helpdeskprestashop.refunds.approve'))
            ->postJson($this->url($this->customer()), [
                'lines' => [['order_detail_id' => 12, 'quantity' => 1]],
                'refund_shipping' => true,
                'destination' => 'voucher',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.order_slip_id', 77);

        Http::assertSent(function (Request $request) {
            $data = $request->data();

            return ($data['action'] ?? null) === 'refunds.issue_partial'
                && ($data['order_id'] ?? null) === 900001
                && ($data['lines'] ?? null) === [['order_detail_id' => 12, 'quantity' => 1]]
                && ($data['refund_shipping'] ?? null) === true
                && ($data['destination'] ?? null) === 'voucher'
                && ($data['max_amount_cents'] ?? null) === 50000
                && isset($data['lookup']['email'])
                && $request->hasHeader('X-Alsernet-Idempotency-Key');
        });
    }

    public function test_shipping_only_refund_without_lines_key_reaches_the_bridge(): void
    {
        // jQuery no serializa "lines: []": un reembolso solo de envío llega sin la clave.
        $this->fakeBridge();

        $this->actingAs($this->agent('helpdeskprestashop.refunds.issue'))
            ->postJson($this->url($this->customer()), ['refund_shipping' => 1, 'destination' => 'payment'])
            ->assertOk()
            ->assertJsonPath('success', true);

        Http::assertSent(function (Request $request) {
            $data = $request->data();

            return ($data['action'] ?? null) === 'refunds.issue_partial'
                && ($data['lines'] ?? null) === []
                && ($data['refund_shipping'] ?? null) === true
                && ($data['max_amount_cents'] ?? null) === 5000;
        });
    }

    public function test_bridge_semantic_rejection_is_reported_as_422(): void
    {
        $this->fakeBridge(['ok_semantic' => false, 'refunded' => false, 'error' => 'exceeds_paid', 'refundable' => 12.5]);

        $this->actingAs($this->agent('helpdeskprestashop.refunds.issue'))
            ->postJson($this->url($this->customer()), ['lines' => [['order_detail_id' => 11, 'quantity' => 1]], 'destination' => 'payment'])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'exceeds_paid');
    }

    public function test_show_returns_refundable_lines_and_the_agent_limit(): void
    {
        $this->fakeBridge();

        $this->actingAs($this->agent('helpdeskprestashop.refunds.issue'))
            ->getJson(route('manager.helpdesk.ps.ext.refunds.orders.show', [$this->customer(), 900001]))
            ->assertOk()
            ->assertJsonPath('data.shipping_refundable', 5.9)
            ->assertJsonPath('limit', 50)
            ->assertJsonPath('can_issue', true);
    }
}
