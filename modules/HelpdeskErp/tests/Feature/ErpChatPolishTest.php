<?php

namespace Modules\HelpdeskErp\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskErp\Database\Seeders\HelpdeskErpPermissionsSeeder;
use Modules\HelpdeskErp\Services\ErpChat\ErpChatDescriptions;
use Modules\HelpdeskErp\Services\ErpChat\ErpChatResponseNormalizer;
use Modules\HelpdeskErp\Services\ErpChat\ErpChatService;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Pulido de Gestión en el chat: descripciones de códigos, albarán por id
 * corto, URL de seguimiento y recarga ligera de solo los pedidos.
 */
class ErpChatPolishTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private const ERP = 101544116;

    private const BASE = 'http://manager.test/api/erp/customer/101544116';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'cache.default' => 'array',
            'queue.default' => 'sync',
            'helpdeskErp.manager_url' => 'http://manager.test',
            'helpdeskErp.bridge_token' => '',
        ]);
        Cache::flush();
        Http::preventStrayRequests();
    }

    private function service(): ErpChatService
    {
        return app(ErpChatService::class);
    }

    /* ── Descripciones ────────────────────────────────────────────────── */

    public function test_orders_keep_manager_descriptions_and_fill_missing_ones_from_config(): void
    {
        Http::fake([self::BASE.'/orders*' => Http::response([
            'success' => true,
            'data' => [
                ['id' => '10102142050', 'number' => '47427', 'status' => '7', 'warehouse' => '1', 'origin' => '4', 'catalog' => '1',
                    'status_description' => 'Servido', 'warehouse_description' => 'POCOMACO', 'origin_description' => 'INTERNET', 'catalog_description' => 'Caza'],
                // Manager anterior: solo códigos.
                ['id' => '10102142061', 'number' => '47439', 'status' => '0', 'warehouse' => '6', 'origin' => '6'],
                // Código desconocido: descripción null, el código se conserva.
                ['id' => '10102142062', 'number' => '47440', 'status' => '99', 'warehouse' => '424242', 'origin' => null],
            ],
            'pagination' => ['limit' => 10, 'offset' => 0, 'count' => 3, 'hasMore' => false],
        ])]);

        $r = $this->service()->orders(self::ERP, ['limit' => 10]);

        $this->assertSame('ok', $r['state']);
        $this->assertSame('INTERNET', $r['data'][0]['origin_description']);
        $this->assertSame('Caza', $r['data'][0]['catalog_description']);

        $this->assertSame('Anulado', $r['data'][1]['status_description']);
        $this->assertSame('ONLINE', $r['data'][1]['warehouse_description']);
        $this->assertSame('DEVOLUCIONES', $r['data'][1]['origin_description']);
        $this->assertArrayHasKey('catalog_description', $r['data'][1]);
        $this->assertNull($r['data'][1]['catalog_description']);

        $this->assertNull($r['data'][2]['status_description']);
        $this->assertNull($r['data'][2]['warehouse_description']);
        $this->assertSame('424242', $r['data'][2]['warehouse']);
    }

    public function test_personal_always_exposes_description_keys(): void
    {
        Http::fake([self::BASE.'/personal' => Http::response(['success' => true, 'data' => [
            'id' => self::ERP, 'language' => '2', 'language_description' => 'Castellano', 'nationality' => '1',
        ]])]);

        $data = $this->service()->personal(self::ERP)['data'];

        $this->assertSame('Castellano', $data['language_description']);
        foreach (['nationality_description', 'fiscal_regime_description', 'category_description', 'customer_type_description'] as $key) {
            $this->assertArrayHasKey($key, $data);
            $this->assertNull($data[$key]);
        }
    }

    public function test_catalogs_are_described(): void
    {
        $out = ErpChatDescriptions::decorate('catalogs', ['catalogs' => [['id' => 1, 'catalog_id' => '5'], ['id' => 2, 'catalog_id' => '1', 'catalog_description' => 'Caza']]]);

        $this->assertSame('Pesca', $out['catalogs'][0]['catalog_description']);
        $this->assertSame('Caza', $out['catalogs'][1]['catalog_description']);
    }

    /* ── Albarán por id corto ─────────────────────────────────────────── */

    public function test_delivery_note_by_short_id_is_resolved_without_guessing_prefixes(): void
    {
        Http::fake(function (Request $request) {
            $path = (string) parse_url($request->url(), PHP_URL_PATH);

            return match (true) {
                str_ends_with($path, '/delivery-notes/101961890') => Http::response(['success' => false, 'error' => 'Delivery note not found for this customer'], 404),
                str_ends_with($path, '/delivery-notes/resolve/101961890') => Http::response(['success' => true, 'data' => [
                    ['id' => 10101961890, 'delivery_id' => '101961890', 'number' => '51498'],
                ]]),
                str_ends_with($path, '/delivery-notes/10101961890') => Http::response(['success' => true, 'data' => [
                    'id' => 10101961890, 'number' => '51498', 'warehouse' => '1', 'type' => '1', 'lines' => [],
                ]]),
                default => Http::response(['success' => false, 'error' => 'unexpected '.$path], 500),
            };
        });

        $r = $this->service()->deliveryNoteDetail(self::ERP, 101961890);

        $this->assertSame('ok', $r['state']);
        $this->assertSame(10101961890, $r['data']['id']);
        $this->assertSame('101961890', $r['resolved_from']);
        $this->assertSame('POCOMACO', $r['data']['warehouse_description']);
        $this->assertSame('Venta', $r['data']['type_description']);
    }

    public function test_ambiguous_or_unknown_short_id_stays_not_found(): void
    {
        Http::fake(function (Request $request) {
            $path = (string) parse_url($request->url(), PHP_URL_PATH);

            return match (true) {
                str_contains($path, '/delivery-notes/resolve/5') => Http::response(['success' => true, 'data' => [['id' => 1], ['id' => 2]]]),
                str_contains($path, '/delivery-notes/resolve/') => Http::response(['success' => true, 'data' => []]),
                default => Http::response(['success' => false, 'error' => 'Delivery note not found for this customer'], 404),
            };
        });

        $this->assertSame('unavailable', $this->service()->deliveryNoteDetail(self::ERP, 5)['state']);
        $this->assertSame('unavailable', $this->service()->deliveryNoteDetail(self::ERP, 6)['state']);
    }

    public function test_manager_without_resolve_endpoint_keeps_original_answer(): void
    {
        Http::fake(function (Request $request) {
            $path = (string) parse_url($request->url(), PHP_URL_PATH);

            return str_contains($path, '/resolve/')
                ? Http::response('<!DOCTYPE html><title>Not Found</title>', 404)
                : Http::response(['success' => false, 'error' => 'Delivery note not found for this customer'], 404);
        });

        $r = $this->service()->deliveryNoteDetail(self::ERP, 101961890);

        $this->assertSame('unavailable', $r['state']);
        $this->assertSame('not_found', $r['reason']);
    }

    /* ── Seguimiento ──────────────────────────────────────────────────── */

    public function test_tracking_url_is_built_from_carrier_and_number(): void
    {
        $n = app(ErpChatResponseNormalizer::class);

        $this->assertSame('https://s.correosexpress.com/c?n=AB%2012', $n->trackingUrl('CORREOSEXPRESS I. STANDARD', 'AB 12'));
        $this->assertStringStartsWith('https://www.correos.es/', (string) $n->trackingUrl('Correos', '99'));
        $this->assertStringStartsWith('https://www.seur.com/', (string) $n->trackingUrl(['id' => '4', 'name' => null], '123'));
        $this->assertStringStartsWith('https://www.mrw.es/', (string) $n->trackingUrl(null, '7', '100000045'));
        $this->assertNull($n->trackingUrl('GRUPSA', '1'));
        $this->assertNull($n->trackingUrl('SEUR', ''));
    }

    public function test_shipping_fills_tracking_url_only_when_manager_does_not(): void
    {
        Http::fake([
            self::BASE.'/orders/1/shipping' => Http::response(['success' => true, 'data' => ['carrier' => 'GLS', 'tracking_number' => 'X1', 'tracking_url' => null, 'delivery_notes' => []]]),
            self::BASE.'/orders/2/shipping' => Http::response(['success' => true, 'data' => ['carrier' => 'GLS', 'tracking_number' => 'X1', 'tracking_url' => 'https://example.test/t/X1', 'delivery_notes' => []]]),
            self::BASE.'/orders/3/shipping' => Http::response(['success' => true, 'data' => ['carrier' => null, 'tracking_number' => null, 'tracking_url' => null, 'delivery_notes' => []]]),
        ]);

        $this->assertStringStartsWith('https://gls-group.com/', (string) $this->service()->orderShipping(self::ERP, 1)['data']['tracking_url']);
        $this->assertSame('https://example.test/t/X1', $this->service()->orderShipping(self::ERP, 2)['data']['tracking_url']);
        $this->assertNull($this->service()->orderShipping(self::ERP, 3)['data']['tracking_url']);
    }

    /* ── Recarga ligera de pedidos ────────────────────────────────────── */

    public function test_overview_orders_route_refetches_only_orders(): void
    {
        if (! Route::has('manager.helpdesk.erp.polish.overview-orders')) {
            $this->markTestSkipped('El cargador de routes/managers.d no está registrado.');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(HelpdeskErpPermissionsSeeder::class);

        $ordersCalls = 0;
        $otherCalls = 0;
        Http::fake(function (Request $request) use (&$ordersCalls, &$otherCalls) {
            $path = (string) parse_url($request->url(), PHP_URL_PATH);

            if (str_ends_with($path, '/orders')) {
                $ordersCalls++;

                return Http::response(['success' => true, 'data' => [
                    ['id' => '10102142050', 'number' => '47427', 'status' => '7', 'origin' => '4', 'date' => '2025-09-23 11:37:08'],
                ], 'pagination' => ['limit' => 10, 'offset' => 0, 'count' => 1, 'hasMore' => false]]);
            }

            $otherCalls++;

            return Http::response(['success' => true, 'data' => ['id' => self::ERP, 'label' => 'ALBERTO']]);
        });

        $customer = Customer::factory()->create([
            'email' => Str::lower(Str::replace('-', '', (string) Str::uuid())).'@test.example',
        ]);
        $customer->linkExternalId('erp', (string) self::ERP);

        $user = User::factory()->create();
        $user->givePermissionTo(['helpdeskerp.view', 'helpdeskerp.orders.view', 'helpdeskerp.addresses.view', 'helpdeskerp.finance.view', 'helpdeskerp.loyalty.view', 'helpdesk.customers.manage']);

        $base = '/panel/helpdesk/customers/'.$customer->id.'/erp';

        $this->actingAs($user)->getJson($base.'/overview')->assertOk();
        $this->assertSame(1, $ordersCalls);
        $othersAfterOverview = $otherCalls;

        $this->actingAs($user)->getJson($base.'/overview/orders')
            ->assertOk()
            ->assertJsonPath('partial', 'orders')
            ->assertJsonPath('data.sections.orders.state', 'ok')
            ->assertJsonPath('data.sections.orders.data.0.origin_description', 'INTERNET')
            ->assertJsonPath('data.sections.summary.cached', true);

        // Solo los pedidos vuelven al manager; el resto sale de la caché.
        $this->assertSame(2, $ordersCalls);
        $this->assertSame($othersAfterOverview, $otherCalls);
    }
}
