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
 * Resolver devolución (pieza 35): cambio de estado de la RMA con el puente
 * simulado. Denegar exige motivo; sin permiso no llega nada a PrestaShop.
 */
class RefundsRmaTest extends TestCase
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
            'helpdeskprestashop.ext.refunds.rma_states' => ['pending' => 1, 'approved' => 2, 'received' => 3, 'denied' => 4, 'completed' => 5],
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
        return Customer::factory()->create(['email' => 'rma-'.uniqid().'@example.com']);
    }

    private function stateUrl(Customer $customer, int $rma = 218): string
    {
        return route('manager.helpdesk.ps.ext.refunds.rma.state', [$customer, $rma]);
    }

    public function test_agent_without_resolve_permission_is_forbidden(): void
    {
        Http::fake();

        $this->actingAs($this->agent())
            ->postJson($this->stateUrl($this->customer()), ['state_id' => 2])
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_denying_without_a_reason_is_a_validation_error(): void
    {
        Http::fake();

        $this->actingAs($this->agent('helpdeskprestashop.returns.resolve'))
            ->postJson($this->stateUrl($this->customer()), ['state_id' => 4, 'message' => '  '])
            ->assertStatus(422)
            ->assertJsonValidationErrors('message');

        Http::assertNothingSent();
    }

    public function test_state_change_reaches_the_bridge_with_idempotency_key(): void
    {
        Http::fake([$this->apiUrl => Http::response(['ok' => true, 'data' => [
            'updated' => true, 'id' => 218, 'order_id' => 816880, 'previous_state_id' => 1,
            'state_id' => 2, 'state_name' => 'A la espera del paquete', 'notified' => true,
        ]])]);

        $this->actingAs($this->agent('helpdeskprestashop.returns.resolve'))
            ->postJson($this->stateUrl($this->customer()), ['state_id' => 2, 'notify' => true, 'message' => 'Hemos aprobado tu devolución.'])
            ->assertOk()
            ->assertJsonPath('data.state_name', 'A la espera del paquete');

        Http::assertSent(function (Request $request) {
            $data = $request->data();

            return ($data['action'] ?? null) === 'refunds.rma_set_state'
                && ($data['return_id'] ?? null) === 218
                && ($data['state_id'] ?? null) === 2
                && ($data['notify'] ?? null) === true
                && ! array_key_exists('message', $data)
                && $request->hasHeader('X-Alsernet-Idempotency-Key');
        });
    }

    public function test_same_state_is_reported_as_422(): void
    {
        Http::fake([$this->apiUrl => Http::response(['ok' => true, 'data' => ['ok_semantic' => false, 'updated' => false, 'error' => 'same_state']])]);

        $this->actingAs($this->agent('helpdeskprestashop.returns.resolve'))
            ->postJson($this->stateUrl($this->customer()), ['state_id' => 2])
            ->assertStatus(422)
            ->assertJsonPath('message', 'La devolución ya está en ese estado.');
    }

    public function test_rma_of_another_customer_is_not_exposed(): void
    {
        // El puente responde 404 cuando la RMA no es del cliente del lookup.
        Http::fake([$this->apiUrl => Http::response(['ok' => false, 'error' => 'unknown action or customer not found'], 404)]);

        // Rechazo de negocio (404 {ok:false}), no caída del puente: 422.
        $this->actingAs($this->agent('helpdeskprestashop.returns.resolve'))
            ->getJson(route('manager.helpdesk.ps.ext.refunds.rma.show', [$this->customer(), 999]))
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_show_returns_detail_and_whether_the_agent_can_resolve(): void
    {
        Http::fake([$this->apiUrl => Http::response(['ok' => true, 'data' => [
            'id' => 218, 'order_id' => 816880, 'order_reference' => 'BPGCSMHNY', 'state_id' => 1, 'reason' => 'Defectuoso',
            'items' => [], 'states' => [['id' => 1, 'name' => 'A la espera de confirmación'], ['id' => 2, 'name' => 'A la espera del paquete']],
        ]])]);

        $this->actingAs($this->agent())
            ->getJson(route('manager.helpdesk.ps.ext.refunds.rma.show', [$this->customer(), 218]))
            ->assertOk()
            ->assertJsonPath('data.reason', 'Defectuoso')
            ->assertJsonPath('can_resolve', false);
    }
}
