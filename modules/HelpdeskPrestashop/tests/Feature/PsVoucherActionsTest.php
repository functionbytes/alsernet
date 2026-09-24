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
 * POST /customers/{customer}/ps/vouchers — vale de compensación desde el
 * chat. Límite por vale según permiso; por encima, 422 + needs_approval y
 * nada llega a PrestaShop.
 */
class PsVoucherActionsTest extends TestCase
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
            'helpdeskprestashop.vouchers.agent_limit' => 25,
            'helpdeskprestashop.vouchers.approver_limit' => 150,
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
        return Customer::factory()->create(['email' => 'vale-'.uniqid().'@example.com']);
    }

    private function payload(float $amount): array
    {
        return ['amount' => $amount, 'validity_days' => 30, 'reason' => 'retraso'];
    }

    public function test_agent_without_voucher_permission_is_forbidden(): void
    {
        Http::fake();

        $this->actingAs($this->agent())
            ->postJson(route('manager.helpdesk.customers.ps.vouchers.store', $this->customer()), $this->payload(10))
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_amount_over_agent_limit_needs_approval_and_never_reaches_prestashop(): void
    {
        Http::fake();

        $this->actingAs($this->agent('helpdeskprestashop.vouchers.create'))
            ->postJson(route('manager.helpdesk.customers.ps.vouchers.store', $this->customer()), $this->payload(30))
            ->assertStatus(422)
            ->assertJsonPath('needs_approval', true)
            ->assertJsonPath('limit', 25);

        Http::assertNothingSent();
    }

    public function test_approver_can_create_above_agent_limit(): void
    {
        Http::fake([$this->apiUrl => Http::response(['ok' => true, 'data' => [
            'created' => true, 'id' => 9, 'code' => 'GES-ABCDEF12', 'amount' => 40.0, 'date_to' => '2026-10-24 23:59:59',
        ]])]);

        $this->actingAs($this->agent('helpdeskprestashop.vouchers.approve'))
            ->postJson(route('manager.helpdesk.customers.ps.vouchers.store', $this->customer()), $this->payload(40))
            ->assertOk()
            ->assertJsonPath('data.code', 'GES-ABCDEF12');

        Http::assertSent(function (Request $request) {
            $data = $request->data();

            return ($data['action'] ?? null) === 'customer.create_voucher'
                && ($data['amount_cents'] ?? null) === 4000
                && ($data['validity_days'] ?? null) === 30
                && ($data['reason'] ?? null) === 'Retraso en la entrega'
                && $request->hasHeader('X-Alsernet-Idempotency-Key');
        });
    }

    public function test_bridge_rejection_is_reported_as_422(): void
    {
        Http::fake([$this->apiUrl => Http::response(['ok' => true, 'data' => ['created' => false, 'error' => 'invalid_amount_or_validity']])]);

        $this->actingAs($this->agent('helpdeskprestashop.vouchers.create'))
            ->postJson(route('manager.helpdesk.customers.ps.vouchers.store', $this->customer()), $this->payload(10))
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_reason_must_be_one_of_the_configured_ones(): void
    {
        Http::fake();

        $this->actingAs($this->agent('helpdeskprestashop.vouchers.create'))
            ->postJson(route('manager.helpdesk.customers.ps.vouchers.store', $this->customer()), ['amount' => 10, 'validity_days' => 30, 'reason' => 'porque sí'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('reason');

        Http::assertNothingSent();
    }
}
