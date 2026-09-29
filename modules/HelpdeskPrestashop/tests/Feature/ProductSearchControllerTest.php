<?php

namespace Modules\HelpdeskPrestashop\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery\MockInterface;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskPrestashop\Database\Seeders\HelpdeskPrestashopPermissionsSeeder;
use Modules\HelpdeskPrestashop\Exceptions\PsUpstreamException;
use Modules\HelpdeskPrestashop\Services\PrestashopContextService;
use Modules\HelpdeskPrestashop\Services\PrestashopProductQueryService;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Endpoints de solo lectura del tab "Tienda" del inbox: detalle/alternativas/
 * búsqueda de producto y categorías (ProductSearchController), y los datos
 * propios del cliente — direcciones, pedidos, devoluciones, vales, mensajes,
 * wishlist, reembolsos — más las provincias (PsCustomerDataController).
 *
 * Los tests pegan por route(), nunca instanciando la clase, y solo comprueban
 * la forma del JSON.
 *
 * Ambos servicios (bridge HTTP + consulta directa a la BD de PrestaShop) se
 * sustituyen por mocks: ningún caso sale a la red ni toca una BD externa.
 */
class ProductSearchControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private const EMAIL = 'cliente@example.com';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(HelpdeskPrestashopPermissionsSeeder::class);

        foreach (['helpdesk.customers.view', 'helpdesk.customers.manage', 'helpdesk.manage'] as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
    }

    private function customer(): Customer
    {
        return Customer::factory()->create(['email' => self::EMAIL, 'language' => 'es']);
    }

    /**
     * Agente con acceso a cualquier cliente (helpdesk.customers.manage hace
     * sharesInboxWith=true, evitando montar inboxes en el test) + permisos
     * PS extra opcionales (para categories/countryStates).
     */
    private function agentWith(string ...$extraPermissions): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo([...$extraPermissions, 'helpdesk.customers.view', 'helpdesk.customers.manage']);

        return $user;
    }

    /** Tiene helpdesk.customers.view pero NO comparte bandeja con el cliente. */
    private function agentWithoutCustomerAccess(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['helpdesk.customers.view']);

        return $user;
    }

    private function mockPs(callable $expectations): void
    {
        $this->mock(PrestashopContextService::class, $expectations);
    }

    private function mockPsProducts(callable $expectations): void
    {
        $this->mock(PrestashopProductQueryService::class, $expectations);
    }

    // ─── detail ─────────────────────────────────────────────────────────────

    public function test_detail_requires_access_to_the_customer(): void
    {
        $this->mockPs(fn (MockInterface $m) => $m->shouldNotReceive('getProductById'));

        $this->actingAs($this->agentWithoutCustomerAccess())
            ->getJson(route('manager.helpdesk.customers.ps.products.detail', [$this->customer(), 303]))
            ->assertForbidden();
    }

    public function test_detail_returns_product_with_attributes_and_combinations(): void
    {
        $this->mockPs(function (MockInterface $m) {
            $m->shouldReceive('getProductById')->once()->with(303, 'es')
                ->andReturn(['id' => 303, 'name' => 'Producto 303']);
        });
        $this->mockPsProducts(function (MockInterface $m) {
            $m->shouldReceive('getProductAttributes')->once()->with(303, 'es')
                ->andReturn(['attributes' => [['group_id' => 1]], 'combinations' => [['id' => 9]]]);
        });

        $this->actingAs($this->agentWith())
            ->getJson(route('manager.helpdesk.customers.ps.products.detail', [$this->customer(), 303]))
            ->assertOk()
            ->assertJsonStructure(['success', 'product', 'attributes', 'combinations'])
            ->assertJsonPath('success', true)
            ->assertJsonPath('product.id', 303)
            ->assertJsonPath('attributes.0.group_id', 1)
            ->assertJsonPath('combinations.0.id', 9);
    }

    public function test_detail_returns_404_when_product_is_not_found(): void
    {
        $this->mockPs(function (MockInterface $m) {
            $m->shouldReceive('getProductById')->once()->with(999, 'es')->andReturn(null);
        });
        $this->mockPsProducts(function (MockInterface $m) {
            $m->shouldReceive('findById')->once()->with(999, 'es')->andReturn(null);
            $m->shouldNotReceive('getProductAttributes');
        });

        $this->actingAs($this->agentWith())
            ->getJson(route('manager.helpdesk.customers.ps.products.detail', [$this->customer(), 999]))
            ->assertNotFound()
            ->assertJsonPath('success', false);
    }

    // ─── alternatives ───────────────────────────────────────────────────────

    public function test_alternatives_requires_access_to_the_customer(): void
    {
        $this->mockPs(fn (MockInterface $m) => $m->shouldNotReceive('getProductById'));

        $this->actingAs($this->agentWithoutCustomerAccess())
            ->getJson(route('manager.helpdesk.customers.ps.products.alternatives', [$this->customer(), 303]))
            ->assertForbidden();
    }

    public function test_alternatives_returns_other_products_from_the_same_brand_and_category(): void
    {
        $this->mockPs(function (MockInterface $m) {
            $m->shouldReceive('getProductById')->once()->with(303, 'es')
                ->andReturn(['id' => 303, 'brand' => 'Acme', 'category' => 'Herramientas']);
            // Menos de 3 alternativas por marca -> también busca por categoría.
            $m->shouldReceive('searchProducts')->once()->with('Acme', 8, 'es')
                ->andReturn([['id' => 304, 'name' => 'Otro Acme'], ['id' => 303, 'name' => 'Producto 303']]);
            $m->shouldReceive('searchProducts')->once()->with('Herramientas', 8, 'es')
                ->andReturn([['id' => 305, 'name' => 'Otra herramienta']]);
        });

        $this->actingAs($this->agentWith())
            ->getJson(route('manager.helpdesk.customers.ps.products.alternatives', [$this->customer(), 303]))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'products')
            ->assertJsonPath('products.0.id', 304)
            ->assertJsonPath('products.1.id', 305);
    }

    public function test_alternatives_returns_empty_list_when_product_is_not_found(): void
    {
        $this->mockPs(function (MockInterface $m) {
            $m->shouldReceive('getProductById')->once()->with(999, 'es')->andReturn(null);
            $m->shouldNotReceive('searchProducts');
        });
        $this->mockPsProducts(function (MockInterface $m) {
            $m->shouldReceive('findById')->once()->with(999, 'es')->andReturn(null);
        });

        $this->actingAs($this->agentWith())
            ->getJson(route('manager.helpdesk.customers.ps.products.alternatives', [$this->customer(), 999]))
            ->assertOk()
            ->assertJson(['success' => true, 'products' => []]);
    }

    // ─── search ─────────────────────────────────────────────────────────────

    public function test_search_requires_access_to_the_customer(): void
    {
        $this->mockPs(fn (MockInterface $m) => $m->shouldNotReceive('searchProducts'));

        $this->actingAs($this->agentWithoutCustomerAccess())
            ->getJson(route('manager.helpdesk.customers.ps.products.search', $this->customer()).'?q=taladro')
            ->assertForbidden();
    }

    public function test_search_by_text_returns_products_from_the_bridge(): void
    {
        // Con espacio: no coincide con el heurístico de referencia/SKU
        // (looksLikeReference), así que va directa a la búsqueda por texto.
        $this->mockPs(function (MockInterface $m) {
            $m->shouldReceive('searchProducts')->once()->with('taladro percutor', 10, 'es', 0, false)
                ->andReturn([['id' => 1, 'name' => 'Taladro percutor']]);
        });

        $this->actingAs($this->agentWith())
            ->getJson(route('manager.helpdesk.customers.ps.products.search', $this->customer()).'?q=taladro+percutor')
            ->assertOk()
            ->assertJsonStructure(['success', 'count', 'has_more', 'products'])
            ->assertJsonPath('count', 1)
            ->assertJsonPath('has_more', false)
            ->assertJsonPath('products.0.name', 'Taladro percutor');
    }

    public function test_search_without_query_returns_recommended_products_from_history(): void
    {
        $this->mockPs(function (MockInterface $m) {
            $m->shouldReceive('getProductsFromHistory')->once()->with(self::EMAIL, 6)->andReturn([
                ['id' => 5, 'name' => 'Comprado antes'],
            ]);
        });
        $this->mockPsProducts(function (MockInterface $m) {
            // Resuelto en el batch -> nunca cae al fallback por-producto (ps->getProductById).
            $m->shouldReceive('findByIds')->once()->with([5], 'es')
                ->andReturn([5 => ['id' => 5, 'name' => 'Comprado antes (con stock)']]);
        });

        $this->actingAs($this->agentWith())
            ->getJson(route('manager.helpdesk.customers.ps.products.search', $this->customer()))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('products.0.name', 'Comprado antes (con stock)');
    }

    // ─── addresses ──────────────────────────────────────────────────────────

    public function test_addresses_requires_access_to_the_customer(): void
    {
        $this->mockPs(fn (MockInterface $m) => $m->shouldNotReceive('getCustomerAddresses'));

        $this->actingAs($this->agentWithoutCustomerAccess())
            ->getJson(route('manager.helpdesk.customers.ps.addresses', $this->customer()))
            ->assertForbidden();
    }

    public function test_addresses_returns_the_customers_shipping_addresses(): void
    {
        $this->mockPs(function (MockInterface $m) {
            $m->shouldReceive('getCustomerAddresses')->once()->with(self::EMAIL, null)
                ->andReturn([['id' => 1, 'city' => 'Madrid']]);
        });

        $this->actingAs($this->agentWith())
            ->getJson(route('manager.helpdesk.customers.ps.addresses', $this->customer()))
            ->assertOk()
            ->assertJson(['success' => true, 'addresses' => [['id' => 1, 'city' => 'Madrid']]]);
    }

    // ─── orders (contexto completo) ─────────────────────────────────────────

    public function test_orders_requires_access_to_the_customer(): void
    {
        $this->mockPs(fn (MockInterface $m) => $m->shouldNotReceive('getCustomerContextOrFail'));

        $this->actingAs($this->agentWithoutCustomerAccess())
            ->getJson(route('manager.helpdesk.customers.ps.orders', $this->customer()))
            ->assertForbidden();
    }

    public function test_orders_returns_the_full_context_payload(): void
    {
        $customer = $this->customer();

        $this->mockPs(function (MockInterface $m) use ($customer) {
            $m->shouldReceive('peekCachedContext')->once()->with(self::EMAIL)->andReturn(null);
            $m->shouldReceive('getCustomerContextOrFail')->once()
                ->with(self::EMAIL, $customer->id, null)
                ->andReturn([
                    'customer' => ['found' => true, 'id' => 42],
                    'orders' => [['id' => 900]],
                    'carts' => [],
                    'fetched_at' => 1234567890,
                ]);
        });

        $this->actingAs($this->agentWith())
            ->getJson(route('manager.helpdesk.customers.ps.orders', $customer))
            ->assertOk()
            ->assertJsonStructure(['success', 'bridge', 'fetched_at', 'customer', 'orders', 'carts', 'addresses', 'returns', 'vouchers', 'refunds', 'messages', 'wishlist'])
            ->assertJsonPath('success', true)
            ->assertJsonPath('bridge', 'ok')
            ->assertJsonPath('orders.0.id', 900);
    }

    public function test_orders_reports_503_and_stale_flag_when_bridge_is_down(): void
    {
        $customer = $this->customer();

        $this->mockPs(function (MockInterface $m) use ($customer) {
            $m->shouldReceive('peekCachedContext')->once()->with(self::EMAIL)->andReturn(null);
            $m->shouldReceive('getCustomerContextOrFail')->once()
                ->with(self::EMAIL, $customer->id, null)
                ->andThrow(new PsUpstreamException('down'));
        });

        $this->actingAs($this->agentWith())
            ->getJson(route('manager.helpdesk.customers.ps.orders', $customer))
            ->assertStatus(503)
            ->assertJsonPath('success', false)
            ->assertJsonPath('bridge', 'down')
            ->assertJsonPath('stale', false)
            ->assertJsonPath('message', 'PrestaShop no responde ahora mismo.');
    }

    // ─── returns / vouchers / messages / wishlist / refunds ────────────────

    public function test_returns_requires_access_to_the_customer(): void
    {
        $this->mockPs(fn (MockInterface $m) => $m->shouldNotReceive('getCustomerReturns'));

        $this->actingAs($this->agentWithoutCustomerAccess())
            ->getJson(route('manager.helpdesk.customers.ps.returns', $this->customer()))
            ->assertForbidden();
    }

    public function test_returns_lists_the_customers_rma_history(): void
    {
        $this->mockPs(function (MockInterface $m) {
            $m->shouldReceive('getCustomerReturns')->once()->with(self::EMAIL, null)
                ->andReturn([['id' => 12, 'status' => 'pending']]);
        });

        $this->actingAs($this->agentWith())
            ->getJson(route('manager.helpdesk.customers.ps.returns', $this->customer()))
            ->assertOk()
            ->assertJson(['success' => true, 'returns' => [['id' => 12, 'status' => 'pending']]]);
    }

    public function test_vouchers_requires_access_to_the_customer(): void
    {
        $this->mockPs(fn (MockInterface $m) => $m->shouldNotReceive('getCustomerVouchers'));

        $this->actingAs($this->agentWithoutCustomerAccess())
            ->getJson(route('manager.helpdesk.customers.ps.vouchers', $this->customer()))
            ->assertForbidden();
    }

    public function test_vouchers_lists_the_customers_own_vouchers(): void
    {
        $this->mockPs(function (MockInterface $m) {
            $m->shouldReceive('getCustomerVouchers')->once()->with(self::EMAIL, null)
                ->andReturn([['code' => 'ALV-10OFF']]);
        });

        $this->actingAs($this->agentWith())
            ->getJson(route('manager.helpdesk.customers.ps.vouchers', $this->customer()))
            ->assertOk()
            ->assertJson(['success' => true, 'vouchers' => [['code' => 'ALV-10OFF']]]);
    }

    public function test_messages_requires_access_to_the_customer(): void
    {
        $this->mockPs(fn (MockInterface $m) => $m->shouldNotReceive('getCustomerMessages'));

        $this->actingAs($this->agentWithoutCustomerAccess())
            ->getJson(route('manager.helpdesk.customers.ps.messages', $this->customer()))
            ->assertForbidden();
    }

    public function test_messages_lists_the_customers_native_ps_threads(): void
    {
        $this->mockPs(function (MockInterface $m) {
            $m->shouldReceive('getCustomerMessages')->once()->with(self::EMAIL, null)
                ->andReturn([['id' => 3, 'subject' => 'Consulta']]);
        });

        $this->actingAs($this->agentWith())
            ->getJson(route('manager.helpdesk.customers.ps.messages', $this->customer()))
            ->assertOk()
            ->assertJson(['success' => true, 'messages' => [['id' => 3, 'subject' => 'Consulta']]]);
    }

    public function test_wishlist_requires_access_to_the_customer(): void
    {
        $this->mockPs(fn (MockInterface $m) => $m->shouldNotReceive('getCustomerWishlist'));

        $this->actingAs($this->agentWithoutCustomerAccess())
            ->getJson(route('manager.helpdesk.customers.ps.wishlist', $this->customer()))
            ->assertForbidden();
    }

    public function test_wishlist_lists_the_customers_products(): void
    {
        $this->mockPs(function (MockInterface $m) {
            $m->shouldReceive('getCustomerWishlist')->once()->with(self::EMAIL, null)
                ->andReturn([['id' => 77]]);
        });

        $this->actingAs($this->agentWith())
            ->getJson(route('manager.helpdesk.customers.ps.wishlist', $this->customer()))
            ->assertOk()
            ->assertJson(['success' => true, 'items' => [['id' => 77]]]);
    }

    public function test_refunds_requires_access_to_the_customer(): void
    {
        $this->mockPs(fn (MockInterface $m) => $m->shouldNotReceive('getCustomerRefunds'));

        $this->actingAs($this->agentWithoutCustomerAccess())
            ->getJson(route('manager.helpdesk.customers.ps.refunds', $this->customer()))
            ->assertForbidden();
    }

    public function test_refunds_lists_the_customers_real_refunds(): void
    {
        $this->mockPs(function (MockInterface $m) {
            $m->shouldReceive('getCustomerRefunds')->once()->with(self::EMAIL, null)
                ->andReturn([['id' => 8, 'amount' => 19.9]]);
        });

        $this->actingAs($this->agentWith())
            ->getJson(route('manager.helpdesk.customers.ps.refunds', $this->customer()))
            ->assertOk()
            ->assertJson(['success' => true, 'refunds' => [['id' => 8, 'amount' => 19.9]]]);
    }

    // ─── categories (catálogo, no atado a cliente) ──────────────────────────

    public function test_categories_requires_helpdeskprestashop_view_permission(): void
    {
        $this->mockPs(fn (MockInterface $m) => $m->shouldNotReceive('getCategories'));

        $this->actingAs($this->agentWith())
            ->getJson(route('manager.helpdesk.ps.categories'))
            ->assertForbidden();
    }

    public function test_categories_returns_the_ps_category_list(): void
    {
        $this->mockPs(function (MockInterface $m) {
            $m->shouldReceive('getCategories')->once()->with('es')
                ->andReturn([['id' => 2, 'name' => 'Jardín']]);
        });

        $this->actingAs($this->agentWith('helpdeskprestashop.view'))
            ->getJson(route('manager.helpdesk.ps.categories').'?lang=es')
            ->assertOk()
            ->assertJson(['success' => true, 'categories' => [['id' => 2, 'name' => 'Jardín']]]);
    }

    // ─── countryStates (catálogo, no atado a cliente) ───────────────────────

    public function test_country_states_requires_a_prestashop_permission(): void
    {
        $this->mockPs(fn (MockInterface $m) => $m->shouldNotReceive('getCountryStates'));

        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson(route('manager.helpdesk.ps.country-states'))
            ->assertForbidden();
    }

    public function test_country_states_returns_the_list_for_a_country(): void
    {
        $this->mockPs(function (MockInterface $m) {
            $m->shouldReceive('getCountryStates')->once()->with(6)
                ->andReturn([['id' => 353, 'name' => 'Madrid']]);
        });

        $this->actingAs($this->agentWith('helpdeskprestashop.view'))
            ->getJson(route('manager.helpdesk.ps.country-states').'?id_country=6')
            ->assertOk()
            ->assertJson(['success' => true, 'states' => [['id' => 353, 'name' => 'Madrid']]]);
    }

    public function test_country_states_accepts_the_addresses_manage_permission(): void
    {
        $this->mockPs(function (MockInterface $m) {
            $m->shouldReceive('getCountryStates')->once()->with(6)->andReturn([]);
        });

        $this->actingAs($this->agentWith('helpdeskprestashop.addresses.manage'))
            ->getJson(route('manager.helpdesk.ps.country-states'))
            ->assertOk();
    }
}
