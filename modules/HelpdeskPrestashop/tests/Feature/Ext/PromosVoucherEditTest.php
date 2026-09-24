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
 * Pieza 33 · Editar cupón. GET/POST /customers/{customer}/ps/ext/promos/vouchers/{voucher}.
 * El puente (simulado con Http::fake) decide propiedad y usos; aquí se
 * comprueba permiso, validación, límites enviados y cómo se traducen sus
 * respuestas.
 */
class PromosVoucherEditTest extends TestCase
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
            'helpdeskprestashop.ext.promos.edit.max_percent' => 30,
            'helpdeskprestashop.ext.promos.edit.max_quantity' => 5,
            'helpdeskprestashop.ext.promos.edit.max_validity_days' => 365,
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
        return Customer::factory()->create(['email' => 'promos-'.uniqid().'@example.com']);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'mode' => 'edit',
            'amount' => 15,
            'minimum' => 50,
            'date_to' => now()->addDays(30)->toDateString(),
            'quantity' => 1,
        ], $overrides);
    }

    private function voucherRow(array $overrides = []): array
    {
        return array_merge([
            'id' => 77, 'code' => 'GES-ABCDEF12', 'type' => 'amount', 'reduction_amount' => 10.0,
            'reduction_percent' => 0.0, 'minimum_amount' => 0.0, 'quantity' => 1, 'uses' => 0,
            'date_to' => now()->addDays(10)->format('Y-m-d 23:59:59'), 'editable' => true,
            'duplicable' => true, 'blocked_reason' => null, 'restrictions' => ['Sin envío gratis'],
        ], $overrides);
    }

    public function test_user_without_edit_permission_is_forbidden_and_nothing_reaches_prestashop(): void
    {
        Http::fake();

        $this->actingAs($this->agent())
            ->postJson(route('manager.helpdesk.ps.ext.promos.voucher.update', [$this->customer(), 77]), $this->payload())
            ->assertForbidden();

        $this->actingAs($this->agent())
            ->getJson(route('manager.helpdesk.ps.ext.promos.voucher.show', [$this->customer(), 77]))
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_show_returns_voucher_and_agent_limits(): void
    {
        Http::fake([$this->apiUrl => Http::response(['ok' => true, 'data' => ['voucher' => $this->voucherRow()]])]);

        $this->actingAs($this->agent('helpdeskprestashop.vouchers.edit'))
            ->getJson(route('manager.helpdesk.ps.ext.promos.voucher.show', [$this->customer(), 77]))
            ->assertOk()
            ->assertJsonPath('data.code', 'GES-ABCDEF12')
            ->assertJsonPath('data.editable', true)
            ->assertJsonPath('limits.max_amount', fn ($v) => (float) $v === 25.0)
            ->assertJsonPath('limits.max_quantity', 5);

        Http::assertSent(fn (Request $r) => ($r->data()['action'] ?? null) === 'promos.voucher_info'
            && ($r->data()['voucher_id'] ?? null) === 77
            && ! empty($r->data()['lookup']['email']));
    }

    public function test_show_of_a_voucher_that_is_not_the_customers_is_404(): void
    {
        Http::fake([$this->apiUrl => Http::response(['ok' => true, 'data' => ['voucher' => null, 'ok_semantic' => false, 'error' => 'not_found']])]);

        $this->actingAs($this->agent('helpdeskprestashop.vouchers.edit'))
            ->getJson(route('manager.helpdesk.ps.ext.promos.voucher.show', [$this->customer(), 999]))
            ->assertNotFound();
    }

    public function test_edit_sends_values_in_cents_with_the_agent_limits_and_an_idempotency_key(): void
    {
        Http::fake([$this->apiUrl => Http::response(['ok' => true, 'data' => [
            'saved' => true, 'mode' => 'edit', 'source_id' => 77, 'voucher' => $this->voucherRow(['reduction_amount' => 15.0]),
        ]])]);

        $dateTo = now()->addDays(30)->toDateString();

        $this->actingAs($this->agent('helpdeskprestashop.vouchers.edit'))
            ->postJson(route('manager.helpdesk.ps.ext.promos.voucher.update', [$this->customer(), 77]), $this->payload(['date_to' => $dateTo]))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.mode', 'edit');

        Http::assertSent(function (Request $request) use ($dateTo) {
            $data = $request->data();

            return ($data['action'] ?? null) === 'promos.voucher_edit'
                && ($data['mode'] ?? null) === 'edit'
                && ($data['voucher_id'] ?? null) === 77
                && ($data['amount_cents'] ?? null) === 1500
                && ($data['minimum_cents'] ?? null) === 5000
                && ($data['date_to'] ?? null) === $dateTo
                && ($data['quantity'] ?? null) === 1
                && ($data['max_amount_cents'] ?? null) === 2500
                && ($data['max_quantity'] ?? null) === 5
                && $request->hasHeader('X-Alsernet-Idempotency-Key');
        });
    }

    public function test_approver_gets_the_higher_amount_limit(): void
    {
        Http::fake([$this->apiUrl => Http::response(['ok' => true, 'data' => [
            'saved' => true, 'mode' => 'edit', 'voucher' => $this->voucherRow(),
        ]])]);

        $this->actingAs($this->agent('helpdeskprestashop.vouchers.edit', 'helpdeskprestashop.vouchers.approve'))
            ->postJson(route('manager.helpdesk.ps.ext.promos.voucher.update', [$this->customer(), 77]), $this->payload(['amount' => 80]))
            ->assertOk();

        Http::assertSent(fn (Request $r) => ($r->data()['max_amount_cents'] ?? null) === 15000);
    }

    public function test_used_voucher_is_rejected_with_the_uses_so_the_modal_can_offer_duplicate(): void
    {
        Http::fake([$this->apiUrl => Http::response(['ok' => true, 'data' => [
            'ok_semantic' => false, 'error' => 'voucher_used', 'uses' => 2,
        ]])]);

        $this->actingAs($this->agent('helpdeskprestashop.vouchers.edit'))
            ->postJson(route('manager.helpdesk.ps.ext.promos.voucher.update', [$this->customer(), 77]), $this->payload())
            ->assertStatus(422)
            ->assertJsonPath('error', 'voucher_used')
            ->assertJsonPath('uses', 2);
    }

    public function test_duplicate_mode_is_forwarded_and_returns_the_new_code(): void
    {
        Http::fake([$this->apiUrl => Http::response(['ok' => true, 'data' => [
            'saved' => true, 'mode' => 'duplicate', 'source_id' => 77,
            'voucher' => $this->voucherRow(['id' => 78, 'code' => 'GES-NUEVOAB12']),
        ]])]);

        $this->actingAs($this->agent('helpdeskprestashop.vouchers.edit'))
            ->postJson(route('manager.helpdesk.ps.ext.promos.voucher.update', [$this->customer(), 77]), $this->payload(['mode' => 'duplicate']))
            ->assertOk()
            ->assertJsonPath('data.voucher.code', 'GES-NUEVOAB12');

        Http::assertSent(fn (Request $r) => ($r->data()['mode'] ?? null) === 'duplicate');
    }

    public function test_over_limit_from_the_bridge_is_a_422_with_the_limit_in_the_message(): void
    {
        Http::fake([$this->apiUrl => Http::response(['ok' => true, 'data' => [
            'ok_semantic' => false, 'error' => 'over_limit', 'limit' => 25, 'unit' => 'amount',
        ]])]);

        $this->actingAs($this->agent('helpdeskprestashop.vouchers.edit'))
            ->postJson(route('manager.helpdesk.ps.ext.promos.voucher.update', [$this->customer(), 77]), $this->payload(['amount' => 40]))
            ->assertStatus(422)
            ->assertJsonPath('error', 'over_limit')
            ->assertJsonPath('message', 'Supera tu límite para subir el cupón (25,00 €).');
    }

    public function test_duplicating_a_gift_voucher_is_rejected_with_its_own_message(): void
    {
        Http::fake([$this->apiUrl => Http::response(['ok' => true, 'data' => [
            'ok_semantic' => false, 'error' => 'gift_rule',
        ]])]);

        $this->actingAs($this->agent('helpdeskprestashop.vouchers.edit'))
            ->postJson(route('manager.helpdesk.ps.ext.promos.voucher.update', [$this->customer(), 77]), $this->payload(['mode' => 'duplicate']))
            ->assertStatus(422)
            ->assertJsonPath('error', 'gift_rule')
            ->assertJsonPath('message', 'El cupón incluye un producto de regalo: no se puede duplicar desde el chat.');
    }

    public function test_voucher_of_another_customer_is_404(): void
    {
        Http::fake([$this->apiUrl => Http::response(['ok' => true, 'data' => ['ok_semantic' => false, 'error' => 'not_found']])]);

        $this->actingAs($this->agent('helpdeskprestashop.vouchers.edit'))
            ->postJson(route('manager.helpdesk.ps.ext.promos.voucher.update', [$this->customer(), 77]), $this->payload())
            ->assertNotFound();
    }

    public function test_past_expiry_and_bad_mode_fail_validation_without_calling_prestashop(): void
    {
        Http::fake();
        $user = $this->agent('helpdeskprestashop.vouchers.edit');

        $this->actingAs($user)
            ->postJson(route('manager.helpdesk.ps.ext.promos.voucher.update', [$this->customer(), 77]), $this->payload(['date_to' => now()->subDay()->toDateString()]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('date_to');

        $this->actingAs($user)
            ->postJson(route('manager.helpdesk.ps.ext.promos.voucher.update', [$this->customer(), 77]), $this->payload(['mode' => 'delete']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('mode');

        Http::assertNothingSent();
    }

    public function test_bridge_down_is_reported_as_503(): void
    {
        Http::fake([$this->apiUrl => Http::response('boom', 500)]);

        $this->actingAs($this->agent('helpdeskprestashop.vouchers.edit'))
            ->postJson(route('manager.helpdesk.ps.ext.promos.voucher.update', [$this->customer(), 77]), $this->payload())
            ->assertStatus(503)
            ->assertJsonPath('success', false);
    }
}
