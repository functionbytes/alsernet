<?php

namespace Modules\HelpdeskPrestashop\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery\MockInterface;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskPrestashop\Database\Seeders\HelpdeskPrestashopPermissionsSeeder;
use Modules\HelpdeskPrestashop\Services\PrestashopContextService;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * DELETE .../ps/cart/{cart}/voucher — autorización, validación y traducción de
 * las respuestas del bridge. El servicio se sustituye por un mock: ningún caso
 * sale a la red ni toca PrestaShop.
 */
class PsCartVoucherRemovalTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private const EMAIL = 'cliente@example.com';

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

    private function agentWith(string $psPermission): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo([$psPermission, 'helpdesk.customers.update', 'helpdesk.customers.manage']);

        return $user;
    }

    private function remove(User $user, Customer $customer, array $body = ['code' => 'ALV-10OFF'])
    {
        return $this->actingAs($user)
            ->deleteJson(route('manager.helpdesk.ps.cart.voucher.remove', [$customer, 55]), $body);
    }

    public function test_requires_carts_manage_permission(): void
    {
        $this->mock(PrestashopContextService::class, fn (MockInterface $m) => $m->shouldNotReceive('removeCartVoucher'));

        // orders.manage no hereda carts.manage.
        $this->remove($this->agentWith('helpdeskprestashop.orders.manage'), $this->customer())
            ->assertForbidden();
    }

    public function test_requires_access_to_the_customer(): void
    {
        $this->mock(PrestashopContextService::class, fn (MockInterface $m) => $m->shouldNotReceive('removeCartVoucher'));

        $user = User::factory()->create();
        $user->givePermissionTo(['helpdeskprestashop.carts.manage', 'helpdesk.customers.update']);

        $this->remove($user, $this->customer())->assertForbidden();
    }

    public function test_code_is_required(): void
    {
        $this->mock(PrestashopContextService::class, fn (MockInterface $m) => $m->shouldNotReceive('removeCartVoucher'));

        $this->remove($this->agentWith('helpdeskprestashop.carts.manage'), $this->customer(), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('code');
    }

    public function test_removes_voucher_and_forgets_customer_cache(): void
    {
        $this->mock(PrestashopContextService::class, function (MockInterface $m) {
            $m->shouldReceive('removeCartVoucher')
                ->once()
                ->with(55, 'ALV-10OFF', self::EMAIL, null, \Mockery::type('string'))
                ->andReturn(['removed' => true, 'cart_id' => 55, 'code' => 'ALV-10OFF']);
            $m->shouldReceive('forgetCache')->once()->with(self::EMAIL);
        });

        $this->remove($this->agentWith('helpdeskprestashop.carts.manage'), $this->customer())
            ->assertOk()
            ->assertJson(['success' => true, 'data' => ['code' => 'ALV-10OFF']]);
    }

    public function test_voucher_not_applied_is_reported_and_cache_is_kept(): void
    {
        $this->mock(PrestashopContextService::class, function (MockInterface $m) {
            $m->shouldReceive('removeCartVoucher')->once()
                ->andReturn(['removed' => false, 'error' => 'voucher_not_applied']);
            $m->shouldNotReceive('forgetCache');
        });

        $this->remove($this->agentWith('helpdeskprestashop.carts.manage'), $this->customer())
            ->assertUnprocessable()
            ->assertJson(['success' => false, 'message' => 'Ese cupón ya no está aplicado al carrito.']);
    }

    public function test_unknown_code_is_reported(): void
    {
        $this->mock(PrestashopContextService::class, function (MockInterface $m) {
            $m->shouldReceive('removeCartVoucher')->once()
                ->andReturn(['removed' => false, 'error' => 'voucher_not_found']);
            $m->shouldNotReceive('forgetCache');
        });

        $this->remove($this->agentWith('helpdeskprestashop.carts.manage'), $this->customer())
            ->assertUnprocessable()
            ->assertJson(['message' => 'El código no existe.']);
    }

    public function test_cart_not_owned_by_customer_returns_generic_error(): void
    {
        // El bridge responde null cuando el carrito no pertenece al cliente.
        $this->mock(PrestashopContextService::class, function (MockInterface $m) {
            $m->shouldReceive('removeCartVoucher')->once()->andReturn(null);
            $m->shouldNotReceive('forgetCache');
        });

        $this->remove($this->agentWith('helpdeskprestashop.carts.manage'), $this->customer())
            ->assertUnprocessable()
            ->assertJson(['message' => 'No se pudo quitar el cupón.']);
    }
}
