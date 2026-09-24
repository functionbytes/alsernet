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
 * Extensión "erpbridge": tarjeta "En Gestión (ERP)" del workspace de pedido.
 *
 * Bridge PS, API REST de Gestión y manager van simulados con Http::fake —
 * nunca se llama a la tienda ni a Oracle reales. Los XML/JSON de los dobles
 * reproducen respuestas reales del pedido PS 816880 (cliente PS 124515 =
 * cliente de Gestión 193862), recortadas.
 */
class ErpbridgeTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private string $apiUrl = 'https://ps.test/modules/alsernetbridge/api.php';

    private string $gestion = 'http://gestion.test:8080/api-gestion/';

    private string $manager = 'http://manager.test/api/erp/customer/';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'cache.default' => 'array',
            'helpdeskprestashop.api_url' => $this->apiUrl,
            'helpdeskprestashop.webhook_secret' => 'test-secret-for-hmac',
            'helpdeskprestashop.ext.erpbridge.gestion_url' => 'http://gestion.test:8080/api-gestion',
            'helpdeskprestashop.ext.erpbridge.cache_ttl' => 0,
            'helpdeskErp.manager_url' => 'http://manager.test',
            'helpdeskErp.bridge_token' => '',
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
        return Customer::factory()->create(['email' => 'erpbridge-'.uniqid().'@example.com']);
    }

    private function url(Customer $customer, int $order = 816880): string
    {
        return route('manager.helpdesk.ps.ext.erpbridge.show', [$customer, $order]);
    }

    /** @return array<string, mixed> */
    private function psOrder(int $id = 816880, int $psCustomer = 124515): array
    {
        return [
            'id' => $id,
            'reference' => 'BPGCSMHNY',
            'customer_id' => $psCustomer,
            'state_id' => 4,
            'state_name' => 'Enviado',
            'totals' => ['total' => 36.98],
            'created_at' => '2026-02-20 17:45:57',
            'lines' => [],
        ];
    }

    private function gestionOrderXml(string $erpCustomer = '193862'): string
    {
        return '<?xml version="1.0" encoding="utf-8"?><response><resource>'
            .'<idpedidocli>102183373</idpedidocli><fpedido>2026-02-20</fpedido><npedidocli>11096</npedidocli>'
            .'<identificadororigen>816880</identificadororigen><total_con_impuestos>36.98</total_con_impuestos>'
            .'<lineas_pedido_cliente><resource><total_con_impuestos>28.99</total_con_impuestos><unidades>1.0</unidades>'
            .'<articulo><descripcion>TIRANTES HART 45 Boutons</descripcion><idarticulo>100226158</idarticulo><codigo>WC101186-1</codigo></articulo>'
            .'</resource></lineas_pedido_cliente>'
            .'<almacen><descripcion>POCOMACO</descripcion><idalmacen>1</idalmacen></almacen>'
            .'<envio><coste>7.99</coste><num></num></envio><serie><descripcorta>2026</descripcorta></serie>'
            .'<incidencia_pedido_cliente></incidencia_pedido_cliente>'
            .'<estado><descripcion>Servido</descripcion><idestado>7</idestado></estado>'
            .'<cliente><idcliente>'.$erpCustomer.'</idcliente></cliente>'
            .'</resource></response>';
    }

    private function fakeAll(string $gestionOrderXml, array $overrides = []): void
    {
        Http::fake(array_merge([
            $this->apiUrl => Http::response(['ok' => true, 'data' => $this->psOrder()]),

            $this->gestion.'pedido-cliente/*' => Http::response($gestionOrderXml, 200, ['Content-Type' => 'application/xml']),
            $this->gestion.'pedido-cliente-hist/*' => Http::response(
                '<?xml version="1.0" encoding="utf-8"?><response>'
                .'<resource><idpedidocli>102183373</idpedidocli><estado>2</estado><fecha>2026-02-20 17:49:45</fecha></resource>'
                .'<resource><idpedidocli>102183373</idpedidocli><estado>7</estado><fecha>2026-02-26 08:15:58</fecha></resource>'
                .'</response>', 200),
            $this->gestion.'pedido-cliente-tracking/*' => Http::response(
                '<?xml version="1.0" encoding="utf-8"?><response><resource><idpedidocli>102183373</idpedidocli>'
                .'<fenvio>2026-02-26</fenvio><codtracking>01400F260963</codtracking><url_tracking>https://www.mrw.es/seguimiento/</url_tracking>'
                .'</resource></response>', 200),

            $this->manager.'search/web/124515*' => Http::response(['success' => true, 'exists' => true, 'matched_by' => 'idweb', 'data' => [
                'id' => '193862', 'code_internet' => '124515',
            ]]),
            $this->manager.'193862/orders/10102183373*' => Http::response(['success' => true, 'data' => [
                'id' => 10102183373, 'order_id' => '102183373', 'date' => '2026-02-20 17:45:57',
                'expected_date' => '2026-03-07', 'served_date' => '2026-02-26 00:00:00',
                'origin' => ['id' => '4', 'description' => 'INTERNET'], 'invoiced' => true, 'requested_invoice' => false,
            ]]),
            $this->manager.'193862/delivery-notes/10101992999*' => Http::response(['success' => true, 'data' => [
                'id' => 10101992999, 'number' => '10453',
                'lines' => [['article' => ['id' => 100226158, 'code' => 'WC101186-1', 'description' => 'TIRANTES HART 45 Boutons'], 'units' => 1, 'total_with_taxes' => 28.99]],
            ]]),
            $this->manager.'193862/delivery-notes*' => Http::response(['success' => true, 'data' => [
                ['id' => 10101992999, 'number' => '10453', 'invoice_id' => null, 'date' => '2026-02-26 00:00:00', 'created' => '2026-02-26 08:15:58'],
                ['id' => 10101981426, 'number' => '1159', 'invoice_id' => '101188419', 'date' => '2026-01-07 00:00:00', 'created' => '2026-01-07 14:53:05'],
            ]]),
            // Respuesta real del manager sin GRANT sobre FACTURACLI_CENTRAL.
            $this->manager.'193862/invoices*' => Http::response(['success' => false, 'error' => 'Acceso denegado a la tabla Oracle. Solicitar GRANT SELECT al DBA.']),
        ], $overrides));
    }

    private function assertOnlyReads(): void
    {
        Http::assertNotSent(function (Request $request) {
            // El bridge PS se llama siempre por POST firmado; lo que importa
            // es que la acción sea de lectura. A Gestión y al manager, solo GET.
            if (str_starts_with($request->url(), $this->apiUrl)) {
                return ($request->data()['action'] ?? null) !== 'order.detail';
            }

            return $request->method() !== 'GET';
        });
    }

    public function test_requires_the_erp_permission(): void
    {
        Http::fake();

        $this->actingAs($this->agent('helpdeskprestashop.orders.view'))
            ->getJson($this->url($this->customer()))
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_shows_the_gestion_order_delivery_note_and_real_invoice_error(): void
    {
        $customer = $this->customer();
        $this->fakeAll($this->gestionOrderXml());

        $this->actingAs($this->agent('helpdeskprestashop.orders.view', 'helpdeskprestashop.orders.erp'))
            ->getJson($this->url($customer))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'found')
            ->assertJsonPath('data.erp.id', '102183373')
            ->assertJsonPath('data.erp.number', '11096')
            ->assertJsonPath('data.erp.state.name', 'Servido')
            ->assertJsonPath('data.erp.origin', 'INTERNET')
            ->assertJsonPath('data.erp.lines.0.code', 'WC101186-1')
            ->assertJsonPath('data.erp.history.1.name', 'Servido')
            ->assertJsonPath('data.erp.tracking.0.number', '01400F260963')
            // Albarán creado al servirse el pedido y con sus artículos.
            ->assertJsonPath('data.delivery_note.number', '10453')
            ->assertJsonPath('data.delivery_note.matched_by', 'served_time_and_articles')
            // Sin factura en el albarán y sin GRANT: se dice, no se inventa.
            ->assertJsonPath('data.invoice.status', 'denied')
            ->assertJsonPath('data.invoice.number', null)
            ->assertJsonPath('data.invoice.pdf_available', false)
            ->assertJson(['data' => ['invoice' => ['message' => 'El albarán de este pedido no tiene factura asociada en Gestión. Además, las facturas de Gestión no se pueden consultar: Acceso denegado a la tabla Oracle. Solicitar GRANT SELECT al DBA.']]]);

        Http::assertSent(function (Request $request) use ($customer) {
            $data = $request->data();

            return str_starts_with($request->url(), $this->apiUrl)
                && ($data['action'] ?? null) === 'order.detail'
                && ($data['order_id'] ?? null) === 816880
                && ($data['lookup']['email'] ?? null) === strtolower($customer->email);
        });
        Http::assertSent(fn (Request $r) => str_contains($r->url(), 'pedido-cliente/?identificadororigen=816880')
            && $r->method() === 'GET'
            && $r->header('Host') === ['gestion.test']);

        $this->assertOnlyReads();
    }

    public function test_reports_when_gestion_has_no_order_for_that_origin(): void
    {
        $this->fakeAll('<?xml version="1.0" encoding="utf-8"?><response></response>');

        $this->actingAs($this->agent('helpdeskprestashop.orders.view', 'helpdeskprestashop.orders.erp'))
            ->getJson($this->url($this->customer()))
            ->assertOk()
            ->assertJsonPath('data.status', 'not_found')
            ->assertJsonPath('data.erp', null)
            ->assertJsonPath('data.invoice', null);

        // Sin pedido en Gestión no se toca el manager.
        Http::assertNotSent(fn (Request $r) => str_starts_with($r->url(), 'http://manager.test'));
    }

    public function test_hides_a_gestion_order_that_belongs_to_another_erp_customer(): void
    {
        $this->fakeAll($this->gestionOrderXml('100521736'));

        $this->actingAs($this->agent('helpdeskprestashop.orders.view', 'helpdeskprestashop.orders.erp'))
            ->getJson($this->url($this->customer()))
            ->assertOk()
            ->assertJsonPath('data.status', 'mismatch')
            ->assertJsonPath('data.erp', null);

        Http::assertNotSent(fn (Request $r) => str_contains($r->url(), 'delivery-notes') || str_contains($r->url(), 'invoices'));
    }

    public function test_order_of_another_customer_is_not_found_and_gestion_is_not_called(): void
    {
        Http::fake([
            $this->apiUrl => Http::response(['ok' => false, 'error' => 'unknown action or customer not found'], 404),
            '*' => Http::response('', 500),
        ]);

        $this->actingAs($this->agent('helpdeskprestashop.orders.view', 'helpdeskprestashop.orders.erp'))
            ->getJson($this->url($this->customer(), 999))
            ->assertStatus(404)
            ->assertJsonPath('success', false);

        Http::assertNotSent(fn (Request $r) => str_contains($r->url(), 'gestion.test') || str_contains($r->url(), 'manager.test'));
    }

    public function test_gestion_outage_is_an_error_not_a_missing_order(): void
    {
        $this->fakeAll('', [
            $this->gestion.'pedido-cliente/*' => Http::response('Service Unavailable', 503),
        ]);

        $this->actingAs($this->agent('helpdeskprestashop.orders.view', 'helpdeskprestashop.orders.erp'))
            ->getJson($this->url($this->customer()))
            ->assertOk()
            ->assertJsonPath('data.status', 'error')
            ->assertJsonPath('data.erp', null);
    }
}
