<?php

namespace Modules\HelpdeskPrestashop\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery\MockInterface;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskPrestashop\Database\Seeders\HelpdeskPrestashopPermissionsSeeder;
use Modules\HelpdeskPrestashop\Exceptions\PsUpstreamException;
use Modules\HelpdeskPrestashop\Services\PrestashopContextService;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * .../ps/cart/{cart}/address|products|products/quantity — autorización,
 * validación y traducción de las respuestas del bridge para las acciones
 * mutadoras del carrito en vivo (setAddress/addProduct/removeProduct/
 * updateQuantity). removeVoucher/applyVoucher ya están cubiertos en
 * PsCartVoucherRemovalTest. El servicio se sustituye por un mock: ningún
 * caso sale a la red ni toca PrestaShop.
 */
class PsCartActionsTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private const EMAIL = 'cliente@example.com';

    private const CART_ID = 55;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(HelpdeskPrestashopPermissionsSeeder::class);

        foreach (['helpdesk.customers.update', 'helpdesk.customers.manage', 'helpdesk.manage'] as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
    }

    private function customer(): Customer
    {
        return Customer::factory()->create(['email' => self::EMAIL]);
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

    /**
     * Agente sin acceso al cliente (sin customers.manage ni inbox
     * compartido): tiene el permiso PS pero sharesInboxWith() falla.
     */
    private function agentWithoutCustomerAccess(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['helpdeskprestashop.carts.manage', 'helpdesk.customers.update']);

        return $user;
    }

    private function setAddress(User $user, Customer $customer, array $body = ['address_id' => 5])
    {
        return $this->actingAs($user)
            ->postJson(route('manager.helpdesk.ps.cart.address', [$customer, self::CART_ID]), $body);
    }

    private function addProduct(User $user, Customer $customer, array $body = ['product_id' => 10, 'quantity' => 2])
    {
        return $this->actingAs($user)
            ->postJson(route('manager.helpdesk.ps.cart.products.add', [$customer, self::CART_ID]), $body);
    }

    private function removeProduct(User $user, Customer $customer, array $body = ['product_id' => 10])
    {
        return $this->actingAs($user)
            ->deleteJson(route('manager.helpdesk.ps.cart.products.remove', [$customer, self::CART_ID]), $body);
    }

    private function updateQuantity(User $user, Customer $customer, array $body = ['product_id' => 10, 'quantity' => 3])
    {
        return $this->actingAs($user)
            ->patchJson(route('manager.helpdesk.ps.cart.products.quantity', [$customer, self::CART_ID]), $body);
    }

    // ─── setAddress ────────────────────────────────────────────────────────

    public function test_set_address_requires_carts_manage_permission(): void
    {
        $this->mock(PrestashopContextService::class, fn (MockInterface $m) => $m->shouldNotReceive('setCartAddress'));

        // orders.manage no hereda carts.manage.
        $this->setAddress($this->agentWith('helpdeskprestashop.orders.manage'), $this->customer())
            ->assertForbidden();
    }

    public function test_set_address_requires_access_to_the_customer(): void
    {
        $this->mock(PrestashopContextService::class, fn (MockInterface $m) => $m->shouldNotReceive('setCartAddress'));

        $this->setAddress($this->agentWithoutCustomerAccess(), $this->customer())->assertForbidden();
    }

    public function test_set_address_validates_required_fields(): void
    {
        $this->mock(PrestashopContextService::class, fn (MockInterface $m) => $m->shouldNotReceive('setCartAddress'));

        $this->setAddress($this->agentWith('helpdeskprestashop.carts.manage'), $this->customer(), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('address_id');
    }

    public function test_set_address_succeeds_and_forgets_customer_cache(): void
    {
        $this->mock(PrestashopContextService::class, function (MockInterface $m) {
            $m->shouldReceive('setCartAddress')
                ->once()
                ->with(self::CART_ID, 5, 'delivery', self::EMAIL, null, \Mockery::type('string'))
                ->andReturn(['cart_id' => self::CART_ID, 'address_id' => 5, 'type' => 'delivery']);
            $m->shouldReceive('forgetCache')->once()->with(self::EMAIL);
        });

        $this->setAddress($this->agentWith('helpdeskprestashop.carts.manage'), $this->customer())
            ->assertOk()
            ->assertJson(['success' => true, 'data' => ['address_id' => 5]]);
    }

    public function test_set_address_reports_upstream_error(): void
    {
        $this->mock(PrestashopContextService::class, function (MockInterface $m) {
            $m->shouldReceive('setCartAddress')->once()->andThrow(new PsUpstreamException('down'));
            $m->shouldNotReceive('forgetCache');
        });

        $this->setAddress($this->agentWith('helpdeskprestashop.carts.manage'), $this->customer())
            ->assertUnprocessable()
            ->assertJson(['success' => false, 'message' => 'PrestaShop rechazó el cambio (carrito/dirección no válidos o sin acceso).']);
    }

    public function test_set_address_reports_null_result(): void
    {
        $this->mock(PrestashopContextService::class, function (MockInterface $m) {
            $m->shouldReceive('setCartAddress')->once()->andReturn(null);
            $m->shouldNotReceive('forgetCache');
        });

        $this->setAddress($this->agentWith('helpdeskprestashop.carts.manage'), $this->customer())
            ->assertUnprocessable()
            ->assertJson(['success' => false, 'message' => 'No se pudo cambiar la dirección del carrito.']);
    }

    // ─── addProduct ────────────────────────────────────────────────────────

    public function test_add_product_requires_carts_manage_permission(): void
    {
        $this->mock(PrestashopContextService::class, fn (MockInterface $m) => $m->shouldNotReceive('addCartProduct'));

        $this->addProduct($this->agentWith('helpdeskprestashop.orders.manage'), $this->customer())
            ->assertForbidden();
    }

    public function test_add_product_requires_access_to_the_customer(): void
    {
        $this->mock(PrestashopContextService::class, fn (MockInterface $m) => $m->shouldNotReceive('addCartProduct'));

        $this->addProduct($this->agentWithoutCustomerAccess(), $this->customer())->assertForbidden();
    }

    public function test_add_product_validates_required_fields(): void
    {
        $this->mock(PrestashopContextService::class, fn (MockInterface $m) => $m->shouldNotReceive('addCartProduct'));

        $this->addProduct($this->agentWith('helpdeskprestashop.carts.manage'), $this->customer(), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('product_id');
    }

    public function test_add_product_succeeds_and_forgets_customer_cache(): void
    {
        $this->mock(PrestashopContextService::class, function (MockInterface $m) {
            $m->shouldReceive('addCartProduct')
                ->once()
                ->with(self::CART_ID, 10, 2, null, self::EMAIL, null, \Mockery::type('string'))
                ->andReturn(['cart_id' => self::CART_ID, 'product_id' => 10, 'attribute_id' => null, 'quantity' => 2]);
            $m->shouldReceive('forgetCache')->once()->with(self::EMAIL);
        });

        $this->addProduct($this->agentWith('helpdeskprestashop.carts.manage'), $this->customer())
            ->assertOk()
            ->assertJson(['success' => true, 'data' => ['product_id' => 10, 'quantity' => 2]]);
    }

    public function test_add_product_reports_upstream_error(): void
    {
        $this->mock(PrestashopContextService::class, function (MockInterface $m) {
            $m->shouldReceive('addCartProduct')->once()->andThrow(new PsUpstreamException('down'));
            $m->shouldNotReceive('forgetCache');
        });

        $this->addProduct($this->agentWith('helpdeskprestashop.carts.manage'), $this->customer())
            ->assertUnprocessable()
            ->assertJson(['success' => false, 'message' => 'PrestaShop rechazó la operación (carrito/producto no válidos o sin acceso).']);
    }

    public function test_add_product_reports_null_result(): void
    {
        $this->mock(PrestashopContextService::class, function (MockInterface $m) {
            $m->shouldReceive('addCartProduct')->once()->andReturn(null);
            $m->shouldNotReceive('forgetCache');
        });

        $this->addProduct($this->agentWith('helpdeskprestashop.carts.manage'), $this->customer())
            ->assertUnprocessable()
            ->assertJson(['success' => false, 'message' => 'No se pudo añadir el producto al carrito.']);
    }

    // ─── removeProduct ─────────────────────────────────────────────────────

    public function test_remove_product_requires_carts_manage_permission(): void
    {
        $this->mock(PrestashopContextService::class, fn (MockInterface $m) => $m->shouldNotReceive('removeCartProduct'));

        $this->removeProduct($this->agentWith('helpdeskprestashop.orders.manage'), $this->customer())
            ->assertForbidden();
    }

    public function test_remove_product_requires_access_to_the_customer(): void
    {
        $this->mock(PrestashopContextService::class, fn (MockInterface $m) => $m->shouldNotReceive('removeCartProduct'));

        $this->removeProduct($this->agentWithoutCustomerAccess(), $this->customer())->assertForbidden();
    }

    public function test_remove_product_validates_required_fields(): void
    {
        $this->mock(PrestashopContextService::class, fn (MockInterface $m) => $m->shouldNotReceive('removeCartProduct'));

        $this->removeProduct($this->agentWith('helpdeskprestashop.carts.manage'), $this->customer(), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('product_id');
    }

    public function test_remove_product_succeeds_and_forgets_customer_cache(): void
    {
        $this->mock(PrestashopContextService::class, function (MockInterface $m) {
            $m->shouldReceive('removeCartProduct')
                ->once()
                ->with(self::CART_ID, 10, null, self::EMAIL, null, \Mockery::type('string'))
                ->andReturn(['cart_id' => self::CART_ID, 'product_id' => 10, 'attribute_id' => null]);
            $m->shouldReceive('forgetCache')->once()->with(self::EMAIL);
        });

        $this->removeProduct($this->agentWith('helpdeskprestashop.carts.manage'), $this->customer())
            ->assertOk()
            ->assertJson(['success' => true, 'data' => ['product_id' => 10]]);
    }

    public function test_remove_product_reports_upstream_error(): void
    {
        $this->mock(PrestashopContextService::class, function (MockInterface $m) {
            $m->shouldReceive('removeCartProduct')->once()->andThrow(new PsUpstreamException('down'));
            $m->shouldNotReceive('forgetCache');
        });

        $this->removeProduct($this->agentWith('helpdeskprestashop.carts.manage'), $this->customer())
            ->assertUnprocessable()
            ->assertJson(['success' => false, 'message' => 'PrestaShop rechazó la operación (carrito/producto no válidos o sin acceso).']);
    }

    public function test_remove_product_reports_null_result(): void
    {
        $this->mock(PrestashopContextService::class, function (MockInterface $m) {
            $m->shouldReceive('removeCartProduct')->once()->andReturn(null);
            $m->shouldNotReceive('forgetCache');
        });

        $this->removeProduct($this->agentWith('helpdeskprestashop.carts.manage'), $this->customer())
            ->assertUnprocessable()
            ->assertJson(['success' => false, 'message' => 'No se pudo quitar el producto del carrito.']);
    }

    // ─── updateQuantity ────────────────────────────────────────────────────

    public function test_update_quantity_requires_carts_manage_permission(): void
    {
        $this->mock(PrestashopContextService::class, fn (MockInterface $m) => $m->shouldNotReceive('updateCartProductQuantity'));

        $this->updateQuantity($this->agentWith('helpdeskprestashop.orders.manage'), $this->customer())
            ->assertForbidden();
    }

    public function test_update_quantity_requires_access_to_the_customer(): void
    {
        $this->mock(PrestashopContextService::class, fn (MockInterface $m) => $m->shouldNotReceive('updateCartProductQuantity'));

        $this->updateQuantity($this->agentWithoutCustomerAccess(), $this->customer())->assertForbidden();
    }

    public function test_update_quantity_validates_required_fields(): void
    {
        $this->mock(PrestashopContextService::class, fn (MockInterface $m) => $m->shouldNotReceive('updateCartProductQuantity'));

        $this->updateQuantity($this->agentWith('helpdeskprestashop.carts.manage'), $this->customer(), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['product_id', 'quantity']);
    }

    public function test_update_quantity_succeeds_and_forgets_customer_cache(): void
    {
        $this->mock(PrestashopContextService::class, function (MockInterface $m) {
            $m->shouldReceive('updateCartProductQuantity')
                ->once()
                ->with(self::CART_ID, 10, 3, null, self::EMAIL, null, \Mockery::type('string'))
                ->andReturn(['cart_id' => self::CART_ID, 'product_id' => 10, 'quantity' => 3]);
            $m->shouldReceive('forgetCache')->once()->with(self::EMAIL);
        });

        $this->updateQuantity($this->agentWith('helpdeskprestashop.carts.manage'), $this->customer())
            ->assertOk()
            ->assertJson(['success' => true, 'data' => ['product_id' => 10, 'quantity' => 3]]);
    }

    public function test_update_quantity_reports_upstream_error(): void
    {
        $this->mock(PrestashopContextService::class, function (MockInterface $m) {
            $m->shouldReceive('updateCartProductQuantity')->once()->andThrow(new PsUpstreamException('down'));
            $m->shouldNotReceive('forgetCache');
        });

        $this->updateQuantity($this->agentWith('helpdeskprestashop.carts.manage'), $this->customer())
            ->assertUnprocessable()
            ->assertJson(['success' => false, 'message' => 'PrestaShop rechazó la operación (carrito/producto no válidos o sin acceso).']);
    }

    public function test_update_quantity_reports_null_result(): void
    {
        $this->mock(PrestashopContextService::class, function (MockInterface $m) {
            $m->shouldReceive('updateCartProductQuantity')->once()->andReturn(null);
            $m->shouldNotReceive('forgetCache');
        });

        $this->updateQuantity($this->agentWith('helpdeskprestashop.carts.manage'), $this->customer())
            ->assertUnprocessable()
            ->assertJson(['success' => false, 'message' => 'No se pudo actualizar la cantidad.']);
    }
}
