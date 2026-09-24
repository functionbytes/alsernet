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
 * Pieza 05: incidencia de envío → nota interna del pedido en PrestaShop
 * (order.add_note). El texto lo compone el servidor; el bridge (simulado)
 * verifica la propiedad del pedido con el lookup del cliente de la ruta.
 */
class AddressShipClaimTest extends TestCase
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

    private function customer(string $email = ''): Customer
    {
        return Customer::factory()->create(['email' => $email !== '' ? $email : 'envio-'.uniqid().'@example.com']);
    }

    private function url(Customer $customer, int $order = 829575): string
    {
        return route('manager.helpdesk.ps.ext.address.ship-claim', [$customer, $order]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'type' => 'damaged',
            'detail' => 'La caja llegó abierta y el taladro tiene un golpe.',
            'carrier' => 'SEUR',
            'tracking_number' => '2349-88104',
            'conversation_id' => 55,
            'attachments' => [
                ['name' => 'foto-caja.jpg', 'url' => 'https://panel.test/storage/helpdesk/foto-caja.jpg'],
            ],
        ], $overrides);
    }

    public function test_without_ship_claim_permission_is_forbidden(): void
    {
        Http::fake();

        $this->actingAs($this->agent())
            ->postJson($this->url($this->customer()), $this->payload())
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_without_access_to_the_customer_is_forbidden(): void
    {
        Http::fake();

        $user = User::factory()->create();
        $user->givePermissionTo(['helpdeskprestashop.orders.ship_claim', 'helpdesk.customers.update']);

        $this->actingAs($user)
            ->postJson($this->url($this->customer()), $this->payload())
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_type_must_be_one_of_the_configured_ones(): void
    {
        Http::fake();

        $this->actingAs($this->agent('helpdeskprestashop.orders.ship_claim'))
            ->postJson($this->url($this->customer()), $this->payload(['type' => 'perdido_en_marte']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('type');

        Http::assertNothingSent();
    }

    public function test_attachment_urls_must_be_http(): void
    {
        Http::fake();

        $this->actingAs($this->agent('helpdeskprestashop.orders.ship_claim'))
            ->postJson($this->url($this->customer()), $this->payload(['attachments' => [['name' => 'x', 'url' => 'javascript:alert(1)']]]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('attachments.0.url');

        Http::assertNothingSent();
    }

    public function test_claim_is_written_as_an_order_note_in_prestashop(): void
    {
        Http::fake([$this->apiUrl => Http::response(['ok' => true, 'data' => ['note_id' => 12]])]);

        $this->actingAs($this->agent('helpdeskprestashop.orders.ship_claim'))
            ->postJson($this->url($this->customer()), $this->payload())
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.type', 'damaged');

        Http::assertSent(function (Request $request) {
            $data = $request->data();
            $note = (string) ($data['note'] ?? '');

            return ($data['action'] ?? null) === 'order.add_note'
                && (int) ($data['order_id'] ?? 0) === 829575
                && isset($data['lookup'])
                && str_contains($note, 'Incidencia de envío · Dañado')
                && str_contains($note, 'Transportista: SEUR · Seguimiento: 2349-88104')
                && str_contains($note, 'https://panel.test/storage/helpdesk/foto-caja.jpg')
                && str_contains($note, 'Conversación del helpdesk #55')
                && $request->hasHeader('X-Alsernet-Idempotency-Key');
        });
    }

    public function test_semantic_rejection_with_ok_false_is_reported_as_422(): void
    {
        Http::fake([$this->apiUrl => Http::response(['ok' => false, 'error' => 'unknown action or customer not found'])]);

        $this->actingAs($this->agent('helpdeskprestashop.orders.ship_claim'))
            ->postJson($this->url($this->customer()), $this->payload())
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    // Forma real del bridge para un pedido de otro cliente: HTTP 404 con
    // ok=false. Es un rechazo de negocio (el puente responde), no una caída:
    // 422 y sin sumar al circuit breaker.
    public function test_order_of_another_customer_is_not_reported_as_success(): void
    {
        Http::fake([$this->apiUrl => Http::response(['ok' => false, 'error' => 'unknown action or customer not found'], 404)]);

        $this->actingAs($this->agent('helpdeskprestashop.orders.ship_claim'))
            ->postJson($this->url($this->customer()), $this->payload())
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_bridge_down_is_reported_as_503(): void
    {
        Http::fake([$this->apiUrl => Http::response('', 500)]);

        $this->actingAs($this->agent('helpdeskprestashop.orders.ship_claim'))
            ->postJson($this->url($this->customer()), $this->payload())
            ->assertStatus(503);
    }
}
