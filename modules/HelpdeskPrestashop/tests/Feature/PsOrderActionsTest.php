<?php

namespace Modules\HelpdeskPrestashop\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskPrestashop\Database\Seeders\HelpdeskPrestashopPermissionsSeeder;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Capa de autorización + validación de las acciones mutadoras del workspace de
 * pedido PrestaShop. Estos casos NO llegan al bridge: se rechazan antes por
 * permiso, por ownership del cliente o por validación.
 *
 * El pedido va atado al {customer} de la ruta y el email que verifica la
 * propiedad se resuelve server-side desde $customer->email — antes viajaba en
 * el body, permitiendo mutar el pedido de cualquier cliente con order_id+email.
 */
class PsOrderActionsTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['mariadb', 'helpdesk'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(HelpdeskPrestashopPermissionsSeeder::class);

        // Permisos de helpdesk que usan el CustomerPolicy (no los siembra el
        // seeder de PrestaShop).
        foreach (['helpdesk.customers.update', 'helpdesk.customers.manage', 'helpdesk.manage'] as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
    }

    private function customer(string $email = 'cliente@example.com'): Customer
    {
        return Customer::factory()->create(['email' => $email]);
    }

    /**
     * Usuario con el permiso PS indicado + acceso a cualquier cliente
     * (helpdesk.customers.manage hace sharesInboxWith=true, evitando montar
     * inboxes en el test).
     */
    private function agentWith(string $psPermission): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo([$psPermission, 'helpdesk.customers.update', 'helpdesk.customers.manage']);

        return $user;
    }

    // ─── ownership (el fix) ───────────────────────────────────────────────────

    public function test_agent_without_customer_access_cannot_mutate_order(): void
    {
        // Tiene el permiso PS pero NO acceso a ese cliente (sin customers.manage
        // ni inbox compartido) → no puede tocar sus pedidos.
        $user = User::factory()->create();
        $user->givePermissionTo(['helpdeskprestashop.orders.manage', 'helpdesk.customers.update']);
        $customer = $this->customer();

        $this->actingAs($user)
            ->postJson(route('manager.helpdesk.ps.orders.status', [$customer, 833342]), [
                'state_id' => 1,
            ])
            ->assertForbidden();
    }

    public function test_mutation_rejected_when_customer_has_no_email(): void
    {
        $user = $this->agentWith('helpdeskprestashop.orders.manage');
        $customer = $this->customer('');

        $this->actingAs($user)
            ->postJson(route('manager.helpdesk.ps.orders.note', [$customer, 833342]), [
                'note' => 'Hola',
            ])
            ->assertUnprocessable();
    }

    // ─── permiso ──────────────────────────────────────────────────────────────

    public function test_change_status_requires_orders_manage_permission(): void
    {
        $customer = $this->customer();

        $this->actingAs(User::factory()->create())
            ->postJson(route('manager.helpdesk.ps.orders.status', [$customer, 833342]), ['state_id' => 1])
            ->assertForbidden();
    }

    public function test_set_tracking_requires_orders_manage_permission(): void
    {
        $customer = $this->customer();

        $this->actingAs(User::factory()->create())
            ->postJson(route('manager.helpdesk.ps.orders.tracking', [$customer, 833342]), ['tracking_number' => 'TEST123'])
            ->assertForbidden();
    }

    public function test_add_note_requires_orders_manage_permission(): void
    {
        $customer = $this->customer();

        $this->actingAs(User::factory()->create())
            ->postJson(route('manager.helpdesk.ps.orders.note', [$customer, 833342]), ['note' => 'Nota'])
            ->assertForbidden();
    }

    public function test_start_return_requires_orders_return_permission(): void
    {
        $customer = $this->customer();
        // manage pero SIN return no basta.
        $user = $this->agentWith('helpdeskprestashop.orders.manage');

        $this->actingAs($user)
            ->postJson(route('manager.helpdesk.ps.orders.return', [$customer, 833342]), [
                'items' => [['order_detail_id' => 1, 'quantity' => 1]],
            ])
            ->assertForbidden();
    }

    public function test_set_address_requires_orders_manage_permission(): void
    {
        $customer = $this->customer();

        $this->actingAs(User::factory()->create())
            ->postJson(route('manager.helpdesk.ps.orders.address', [$customer, 833342]), ['address_id' => 1])
            ->assertForbidden();
    }

    public function test_send_email_requires_orders_manage_permission(): void
    {
        $customer = $this->customer();

        $this->actingAs(User::factory()->create())
            ->postJson(route('manager.helpdesk.ps.orders.email', [$customer, 833342]), ['type' => 'order_conf'])
            ->assertForbidden();
    }

    public function test_documents_requires_orders_view_permission(): void
    {
        $customer = $this->customer();

        $this->actingAs(User::factory()->create())
            ->getJson(route('manager.helpdesk.ps.orders.documents', [$customer, 833342]))
            ->assertForbidden();
    }

    public function test_order_states_requires_orders_view_permission(): void
    {
        $this->actingAs(User::factory()->create())
            ->getJson(route('manager.helpdesk.ps.order-states'))
            ->assertForbidden();
    }

    // ─── validación (con permiso + acceso) ────────────────────────────────────

    public function test_change_status_validates_required_fields(): void
    {
        $user = $this->agentWith('helpdeskprestashop.orders.manage');
        $customer = $this->customer();

        $this->actingAs($user)
            ->postJson(route('manager.helpdesk.ps.orders.status', [$customer, 833342]), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('state_id');
    }

    public function test_set_tracking_validates_required_fields(): void
    {
        $user = $this->agentWith('helpdeskprestashop.orders.manage');
        $customer = $this->customer();

        $this->actingAs($user)
            ->postJson(route('manager.helpdesk.ps.orders.tracking', [$customer, 833342]), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('tracking_number');
    }

    public function test_add_note_validates_required_fields(): void
    {
        $user = $this->agentWith('helpdeskprestashop.orders.manage');
        $customer = $this->customer();

        $this->actingAs($user)
            ->postJson(route('manager.helpdesk.ps.orders.note', [$customer, 833342]), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('note');
    }

    public function test_start_return_validates_items(): void
    {
        $user = $this->agentWith('helpdeskprestashop.orders.return');
        $customer = $this->customer();

        $this->actingAs($user)
            ->postJson(route('manager.helpdesk.ps.orders.return', [$customer, 833342]), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items');
    }

    public function test_set_address_validates_required_fields(): void
    {
        $user = $this->agentWith('helpdeskprestashop.orders.manage');
        $customer = $this->customer();

        $this->actingAs($user)
            ->postJson(route('manager.helpdesk.ps.orders.address', [$customer, 833342]), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('address_id');
    }

    public function test_send_email_rejects_type_outside_whitelist(): void
    {
        $user = $this->agentWith('helpdeskprestashop.orders.manage');
        $customer = $this->customer();

        $this->actingAs($user)
            ->postJson(route('manager.helpdesk.ps.orders.email', [$customer, 833342]), ['type' => 'hack_template'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('type');
    }
}
