<?php

namespace Modules\HelpdeskPrestashop\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\Events\AdminOrderReceived;
use Modules\Ecommerce\Events\OrderPlaced;
use Modules\Ecommerce\Models\Discount;
use Modules\Ecommerce\Models\Order;
use Modules\Ecommerce\Models\Product;
use Modules\Helpdesk\Database\Seeders\PermissionsSeeder;
use Modules\Helpdesk\Jobs\SendHelpdeskEmailJob;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskPrestashop\Models\AssistedCart;
use Modules\HelpdeskPrestashop\Services\AssistedCartService;
use Nwidart\Modules\Facades\Module;
use Tests\TestCase;

class AssistedCartTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['mariadb', 'helpdesk'];

    private User $agent;

    protected function setUp(): void
    {
        parent::setUp();

        // El carrito asistido usa modelos del módulo Ecommerce (Product/Order/
        // Discount). Ecommerce está deshabilitado en modules_statuses.json, así
        // que sus tablas (ecommerce_products…) no están migradas en la BD de
        // test y estos tests fallarían con "table doesn't exist", enmascarando
        // regresiones reales. Al habilitar Ecommerce vuelven a ejecutarse.
        if (Module::find('Ecommerce')?->isEnabled() !== true) {
            $this->markTestSkipped('Módulo Ecommerce deshabilitado: sus tablas no están migradas en la BD de test.');
        }

        $this->seed(PermissionsSeeder::class);

        $this->agent = User::factory()->create();
        // CustomerPolicy::update() exige helpdesk.customers.update Y ADEMAS
        // compartir inbox con el cliente (AgentInboxCapacity + Conversation)
        // — .manage es el bypass de "gestor" que ya usa la propia policy
        // dentro de ese segundo check, evita construir esos fixtures solo
        // para poder editar el carrito.
        $this->agent->givePermissionTo([
            'helpdesk.conversations.view',
            'helpdesk.conversations.update',
            'helpdesk.customers.view',
            'helpdesk.customers.update',
            'helpdesk.customers.manage',
        ]);
    }

    public function test_agent_can_add_product_and_totals_are_calculated(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 100, 'sale_price' => null]);

        $response = $this->actingAs($this->agent)
            ->postJson(route('manager.helpdesk.customers.cart.items.store', $customer), [
                'product_id' => $product->id,
                'quantity' => 2,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('cart.items_count', 1)
            ->assertJsonPath('cart.units_count', 2)
            ->assertJsonPath('cart.subtotal', 200)
            ->assertJsonPath('cart.total', 200);

        $this->assertDatabaseHas('helpdesk_assisted_carts', [
            'customer_id' => $customer->id,
            'status' => AssistedCart::STATUS_BUILDING,
        ], 'helpdesk');
    }

    public function test_adding_same_product_twice_increments_quantity(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 50, 'sale_price' => null]);

        $this->actingAs($this->agent)
            ->postJson(route('manager.helpdesk.customers.cart.items.store', $customer), ['product_id' => $product->id])
            ->assertOk();

        $response = $this->actingAs($this->agent)
            ->postJson(route('manager.helpdesk.customers.cart.items.store', $customer), ['product_id' => $product->id]);

        $response->assertOk()
            ->assertJsonPath('cart.items_count', 1)
            ->assertJsonPath('cart.units_count', 2);
    }

    public function test_agent_can_update_and_remove_cart_items(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 30, 'sale_price' => null]);

        $this->actingAs($this->agent)
            ->postJson(route('manager.helpdesk.customers.cart.items.store', $customer), ['product_id' => $product->id])
            ->assertOk();

        $item = AssistedCart::query()->forCustomer($customer->id)->building()->first()->items()->first();

        $this->actingAs($this->agent)
            ->patchJson(route('manager.helpdesk.customers.cart.items.update', [$customer, $item->id]), ['quantity' => 5])
            ->assertOk()
            ->assertJsonPath('cart.units_count', 5);

        $this->actingAs($this->agent)
            ->deleteJson(route('manager.helpdesk.customers.cart.items.destroy', [$customer, $item->id]))
            ->assertOk()
            ->assertJsonPath('cart.items_count', 0);
    }

    public function test_agent_can_list_customer_carts(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 10, 'sale_price' => null]);

        $this->actingAs($this->agent)
            ->postJson(route('manager.helpdesk.customers.cart.items.store', $customer), ['product_id' => $product->id])
            ->assertOk();

        $this->actingAs($this->agent)
            ->getJson(route('manager.helpdesk.customers.carts.index', $customer))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('counts.active', 1)
            ->assertJsonCount(1, 'carts');
    }

    public function test_user_without_permission_cannot_modify_cart(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 10, 'sale_price' => null]);
        $stranger = User::factory()->create();

        $this->actingAs($stranger)
            ->postJson(route('manager.helpdesk.customers.cart.items.store', $customer), ['product_id' => $product->id])
            ->assertForbidden();
    }

    public function test_add_item_validates_product_exists(): void
    {
        $customer = Customer::factory()->create();

        $this->actingAs($this->agent)
            ->postJson(route('manager.helpdesk.customers.cart.items.store', $customer), ['product_id' => 999999])
            ->assertUnprocessable();
    }

    // ─── applyDiscount ──────────────────────────────────────────────────────────

    public function test_apply_discount_rejects_invalid_code(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 100, 'sale_price' => null]);

        $this->actingAs($this->agent)
            ->postJson(route('manager.helpdesk.customers.cart.items.store', $customer), ['product_id' => $product->id])
            ->assertOk();

        $this->actingAs($this->agent)
            ->postJson(route('manager.helpdesk.customers.cart.discount', $customer), ['code' => 'NO-EXISTE'])
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('helpdesk_assisted_carts', [
            'customer_id' => $customer->id,
            'discount_code' => null,
        ], 'helpdesk');
    }

    public function test_apply_discount_accepts_valid_code_and_reduces_total(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 100, 'sale_price' => null]);
        $discount = Discount::factory()->create(['code' => 'DESC10', 'value' => 10]);

        $this->actingAs($this->agent)
            ->postJson(route('manager.helpdesk.customers.cart.items.store', $customer), ['product_id' => $product->id])
            ->assertOk();

        $this->actingAs($this->agent)
            ->postJson(route('manager.helpdesk.customers.cart.discount', $customer), ['code' => $discount->code])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('cart.discount_code', 'DESC10')
            ->assertJsonPath('cart.total', 90);
    }

    // ─── generateOrder ──────────────────────────────────────────────────────────

    /**
     * @return array<string, mixed>
     */
    private function validCustomerData(): array
    {
        return [
            'name' => 'Cliente Prueba',
            'email' => 'cliente-'.uniqid().'@example.com',
            'phone' => '600000000',
            'address' => 'Calle Falsa 123',
            'city' => 'Madrid',
            'country' => 'ES',
            'zip_code' => '28001',
        ];
    }

    public function test_agent_can_generate_order_from_cart(): void
    {
        Event::fake([OrderPlaced::class, AdminOrderReceived::class]);

        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 50, 'sale_price' => null]);

        $this->actingAs($this->agent)
            ->postJson(route('manager.helpdesk.customers.cart.items.store', $customer), ['product_id' => $product->id, 'quantity' => 3])
            ->assertOk();

        $response = $this->actingAs($this->agent)
            ->postJson(route('manager.helpdesk.customers.cart.generate-order', $customer), $this->validCustomerData())
            ->assertOk()
            ->assertJsonPath('success', true);

        $orderCode = $response->json('order.code');
        $this->assertDatabaseHas('ecommerce_orders', ['code' => $orderCode, 'total' => 150]);

        $this->assertDatabaseHas('helpdesk_assisted_carts', [
            'customer_id' => $customer->id,
            'status' => AssistedCart::STATUS_ORDERED,
            'order_code' => $orderCode,
        ], 'helpdesk');
    }

    /**
     * La idempotencia vive en AssistedCartService::generateOrder(): dos
     * llamadas sobre el MISMO carrito (la carrera real: dos peticiones casi
     * simultáneas que resolvieron el mismo carrito en construcción) devuelven
     * el mismo pedido, no crean uno duplicado en Ecommerce.
     */
    public function test_generate_order_on_the_same_cart_is_idempotent(): void
    {
        Event::fake([OrderPlaced::class, AdminOrderReceived::class]);

        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 50, 'sale_price' => null]);
        $data = $this->validCustomerData();

        $service = app(AssistedCartService::class);
        $cart = $service->getOrCreateCart($customer);
        $service->addItem($cart, $product->id, 2);

        $first = $service->generateOrder($cart, $data);
        $second = $service->generateOrder($cart, $data);

        $this->assertSame($first->id, $second->id, 'El retry debe devolver el mismo pedido, no crear otro.');
        $this->assertSame(1, Order::where('id', $first->id)->count());
    }

    public function test_generate_order_fails_when_cart_is_empty(): void
    {
        $customer = Customer::factory()->create();

        // Crea el carrito "building" (vacio) sin items.
        $this->actingAs($this->agent)
            ->getJson(route('manager.helpdesk.customers.cart.show', $customer))
            ->assertOk();

        $this->actingAs($this->agent)
            ->postJson(route('manager.helpdesk.customers.cart.generate-order', $customer), $this->validCustomerData())
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_generate_order_validates_required_customer_data(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 20, 'sale_price' => null]);

        $this->actingAs($this->agent)
            ->postJson(route('manager.helpdesk.customers.cart.items.store', $customer), ['product_id' => $product->id])
            ->assertOk();

        $this->actingAs($this->agent)
            ->postJson(route('manager.helpdesk.customers.cart.generate-order', $customer), ['name' => 'Solo nombre'])
            ->assertUnprocessable();
    }

    // ─── sendPaymentLink ────────────────────────────────────────────────────────

    public function test_agent_can_send_payment_link(): void
    {
        // El modulo base Ecommerce esta deshabilitado en modules_statuses.json
        // en este entorno; EcommercePaymentServiceProvider (correctamente)
        // no registra sus rutas mientras Ecommerce este deshabilitado, asi
        // que la ruta que consume sendPaymentLink() no existe aqui.
        if (! Route::has('payment.wompi.mobile-checkout')) {
            $this->markTestSkipped('payment.wompi.mobile-checkout no registrada (modulo Ecommerce deshabilitado en este entorno).');
        }

        Event::fake([OrderPlaced::class, AdminOrderReceived::class]);
        Queue::fake();

        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 75, 'sale_price' => null]);
        $customerData = $this->validCustomerData();

        $this->actingAs($this->agent)
            ->postJson(route('manager.helpdesk.customers.cart.items.store', $customer), ['product_id' => $product->id])
            ->assertOk();

        $response = $this->actingAs($this->agent)
            ->postJson(route('manager.helpdesk.customers.cart.send-payment-link', $customer), $customerData)
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertNotEmpty($response->json('payment_url'));

        Queue::assertPushed(SendHelpdeskEmailJob::class, function (SendHelpdeskEmailJob $job) use ($customerData) {
            $property = new \ReflectionProperty($job, 'recipients');
            $property->setAccessible(true);

            return $property->getValue($job) === [$customerData['email']];
        });

        // sendPaymentLink genera la orden con markConverted: false — el
        // carrito queda 'sent' (esperando pago), no 'ordered' todavia.
        $this->assertDatabaseHas('helpdesk_assisted_carts', [
            'customer_id' => $customer->id,
            'status' => AssistedCart::STATUS_SENT,
        ], 'helpdesk');

        $this->assertDatabaseMissing('helpdesk_assisted_carts', [
            'customer_id' => $customer->id,
            'status' => AssistedCart::STATUS_ORDERED,
        ], 'helpdesk');
    }

    // ─── cancel ──────────────────────────────────────────────────────────────

    public function test_agent_can_cancel_cart_marking_it_abandoned(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 20, 'sale_price' => null]);

        $this->actingAs($this->agent)
            ->postJson(route('manager.helpdesk.customers.cart.items.store', $customer), ['product_id' => $product->id])
            ->assertOk();

        $this->actingAs($this->agent)
            ->postJson(route('manager.helpdesk.customers.cart.cancel', $customer))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('helpdesk_assisted_carts', [
            'customer_id' => $customer->id,
            'status' => AssistedCart::STATUS_ABANDONED,
        ], 'helpdesk');
    }

    public function test_cancel_cart_requires_permission(): void
    {
        $customer = Customer::factory()->create();
        $stranger = User::factory()->create();

        $this->actingAs($stranger)
            ->postJson(route('manager.helpdesk.customers.cart.cancel', $customer))
            ->assertForbidden();
    }
}
