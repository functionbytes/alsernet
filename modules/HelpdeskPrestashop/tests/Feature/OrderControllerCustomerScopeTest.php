<?php

namespace Modules\HelpdeskPrestashop\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Modules\Helpdesk\Models\AgentInboxCapacity;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Inbox;
use Modules\HelpdeskPrestashop\Database\Seeders\HelpdeskPrestashopPermissionsSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * customer_email era nullable — sin el, el pedido se resolvia solo por ID
 * secuencial en el bridge PrestaShop, permitiendo ver/devolver pedidos de
 * OTROS clientes con solo incrementar {order}.
 */
class OrderControllerCustomerScopeTest extends TestCase
{
    use DatabaseTransactions;

    /** Revertir escrituras también en la conexión helpdesk (Customer/Inbox/Conversation). */
    protected $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    protected string $apiUrl = 'http://localhost:8090/modules/alsernetbridge/api.php';

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(HelpdeskPrestashopPermissionsSeeder::class);
        // sharesInboxWith() consulta estos permisos; deben EXISTIR o hasPermissionTo lanza.
        Permission::findOrCreate('helpdesk.manage', 'web');
        Permission::findOrCreate('helpdesk.customers.manage', 'web');

        config([
            'helpdeskprestashop.api_url' => $this->apiUrl,
            'helpdeskprestashop.webhook_secret' => 'test-secret-for-hmac',
            'helpdeskprestashop.cache_ttl' => 300,
            'helpdeskprestashop.http_timeout' => 10,
        ]);
    }

    private function userWithPermission(string ...$permissions): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo($permissions);

        return $user;
    }

    public function test_order_detail_requires_customer_email(): void
    {
        $user = $this->userWithPermission('helpdeskprestashop.orders.view');

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/helpdeskprestashop/orders/42/detail')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('customer_email');
    }

    public function test_order_detail_succeeds_with_customer_email(): void
    {
        // 'owner@example.com' no tiene Customer local (es un prospecto para
        // este test): el controller exige ADEMÁS helpdeskprestashop.prospect.view
        // para ese caso (reservado a roles de confianza — ver el seeder). El
        // camino feliz de este test representa a un actor con autorización
        // completa, no el límite agente/admin que cubren los tests de
        // "forbidden for local customer outside inbox".
        $user = $this->userWithPermission('helpdeskprestashop.orders.view', 'helpdeskprestashop.prospect.view');

        Http::fake([
            $this->apiUrl => Http::response(['ok' => true, 'data' => ['id' => 42, 'reference' => 'ABC123']]),
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/helpdeskprestashop/orders/42/detail?customer_email=owner@example.com')
            ->assertOk();

        Http::assertSent(fn ($req) => ($req->data()['lookup']['email'] ?? null) === 'owner@example.com');
    }

    public function test_start_return_requires_customer_email(): void
    {
        $user = $this->userWithPermission('helpdeskprestashop.orders.return');

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/helpdeskprestashop/orders/42/start-return', [
                'items' => [['order_detail_id' => 1, 'quantity' => 1]],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('customer_email');
    }

    public function test_start_return_succeeds_with_customer_email(): void
    {
        // Mismo motivo que test_order_detail_succeeds_with_customer_email():
        // 'owner@example.com' es un prospecto y ese camino exige el permiso
        // adicional de prospecto.
        $user = $this->userWithPermission('helpdeskprestashop.orders.return', 'helpdeskprestashop.prospect.view');

        Http::fake([
            $this->apiUrl => Http::response(['ok' => true, 'data' => ['id' => 42, 'status' => 'pending']]),
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/helpdeskprestashop/orders/42/start-return', [
                'items' => [['order_detail_id' => 1, 'quantity' => 1]],
                'customer_email' => 'owner@example.com',
            ])
            ->assertOk();

        Http::assertSent(fn ($req) => ($req->data()['lookup']['email'] ?? null) === 'owner@example.com');
    }

    // ── Aislamiento de bandeja (la IDOR real) ────────────────────────────────

    /**
     * Cliente LOCAL del helpdesk cuya conversación vive en una bandeja a la que el
     * agente NO pertenece: aportar su email no debe permitir ver su pedido.
     */
    private function localCustomerOutsideAgentInbox(User $agent): Customer
    {
        $agentInbox = Inbox::create(['name' => 'Bandeja del agente', 'channel_type' => Inbox::CHANNEL_WHATSAPP, 'is_active' => true]);
        AgentInboxCapacity::create(['user_id' => $agent->id, 'inbox_id' => $agentInbox->id, 'max_concurrent' => 5, 'accepts_new' => true]);

        $foreignInbox = Inbox::create(['name' => 'Bandeja ajena', 'channel_type' => Inbox::CHANNEL_WHATSAPP, 'is_active' => true]);
        $customer = Customer::factory()->create(['email' => 'ajeno@example.com']);
        Conversation::factory()->create(['customer_id' => $customer->id, 'inbox_id' => $foreignInbox->id]);

        return $customer;
    }

    public function test_order_detail_forbidden_for_local_customer_outside_inbox(): void
    {
        Http::fake();
        $user = $this->userWithPermission('helpdeskprestashop.orders.view');
        $this->localCustomerOutsideAgentInbox($user);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/helpdeskprestashop/orders/42/detail?customer_email=ajeno@example.com')
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_start_return_forbidden_for_local_customer_outside_inbox(): void
    {
        Http::fake();
        $user = $this->userWithPermission('helpdeskprestashop.orders.return');
        $this->localCustomerOutsideAgentInbox($user);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/helpdeskprestashop/orders/42/start-return', [
                'items' => [['order_detail_id' => 1, 'quantity' => 1]],
                'customer_email' => 'ajeno@example.com',
            ])
            ->assertForbidden();

        Http::assertNothingSent();
    }
}
