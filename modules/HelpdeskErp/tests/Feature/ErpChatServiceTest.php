<?php

namespace Modules\HelpdeskErp\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\HelpdeskErp\Services\ErpChat\ErpChatService;
use Tests\TestCase;

/**
 * Normalización de estados de ErpChatService contra respuestas reales del
 * manager (el manager devuelve errores con HTTP 200 y success:false).
 */
class ErpChatServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private const ERP = 101544116;

    private const BASE = 'http://manager.test/api/erp/customer/101544116';

    private const GRANT = ['success' => false, 'error' => 'Acceso denegado a la tabla Oracle. Solicitar GRANT SELECT al DBA.'];

    private const LOST = ['success' => false, 'error' => 'Lost connection and no reconnector available.'];

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'cache.default' => 'array',
            'queue.default' => 'sync',
            'helpdeskErp.manager_url' => 'http://manager.test',
            'helpdeskErp.bridge_token' => '',
            'helpdeskErp.circuit_failure_threshold' => 3,
        ]);
        Cache::flush();
        Http::preventStrayRequests();
    }

    private function service(): ErpChatService
    {
        return app(ErpChatService::class);
    }

    public function test_grant_error_with_http_200_is_blocked(): void
    {
        Http::fake([self::BASE.'/balance' => Http::response(self::GRANT, 200)]);

        $r = $this->service()->balance(self::ERP);

        $this->assertSame('blocked', $r['state']);
        $this->assertSame('Pendiente de permiso en Oracle', $r['message']);
        $this->assertSame('grant', $r['reason']);
        $this->assertNull($r['data']);
        $this->assertNotEmpty($r['fetched_at']);
    }

    public function test_ora_01031_with_http_500_is_blocked(): void
    {
        Http::fake([self::BASE.'/invoices/55' => Http::response(['success' => false, 'error' => 'ORA-01031: insufficient privileges'], 500)]);

        $this->assertSame('blocked', $this->service()->invoiceDetail(self::ERP, 55)['state']);
    }

    public function test_orders_loading_is_reported_and_never_cached(): void
    {
        Http::fake([self::BASE.'/orders*' => Http::response([
            'success' => true, 'data' => [],
            'pagination' => ['limit' => 10, 'offset' => 0, 'count' => 0, 'hasMore' => false],
            'meta' => ['cached' => false, 'available' => false, 'loading' => true, 'retry_after' => 35],
        ])]);

        $r = $this->service()->orders(self::ERP, ['limit' => 10]);
        $this->service()->orders(self::ERP, ['limit' => 10]);

        $this->assertSame('loading', $r['state']);
        $this->assertSame(35, $r['retry_after']);
        $this->assertSame([], $r['data']);
        Http::assertSentCount(2);
    }

    public function test_missing_endpoint_is_unavailable(): void
    {
        Http::fake([self::BASE.'/returns*' => Http::response('<!DOCTYPE html><title>Not Found</title>', 404)]);

        $r = $this->service()->returns(self::ERP);

        $this->assertSame('unavailable', $r['state']);
        $this->assertSame('endpoint_missing', $r['reason']);
    }

    public function test_not_available_error_code_is_unavailable_with_manager_message(): void
    {
        Http::fake([self::BASE.'/orders/10102138690/history' => Http::response([
            'success' => false, 'error' => 'not_available',
            'message' => 'El ERP no expone un histórico de estados del pedido al usuario de lectura.',
        ], 200)]);

        $r = $this->service()->orderHistory(self::ERP, 10102138690);

        $this->assertSame('unavailable', $r['state']);
        $this->assertSame('not_exposed', $r['reason']);
        $this->assertStringContainsString('no expone', $r['message']);
    }

    public function test_new_endpoints_never_report_errors_other_than_connection(): void
    {
        Http::fake([
            self::BASE.'/orders/5/shipping' => Http::response(['success' => false, 'error' => 'ORA-00904: invalid identifier'], 500),
            self::BASE.'/returns*' => Http::response(['success' => false, 'error' => 'Undefined column'], 200),
        ]);

        $this->assertSame('unavailable', $this->service()->orderShipping(self::ERP, 5)['state']);
        $this->assertSame('unavailable', $this->service()->returns(self::ERP)['state']);
    }

    public function test_connection_failure_is_down(): void
    {
        Http::fake([self::BASE.'/contact' => Http::failedConnection('cURL error 28: timeout')]);

        $r = $this->service()->contact(self::ERP);

        $this->assertSame('down', $r['state']);
        $this->assertSame('connection', $r['reason']);
    }

    public function test_lost_connection_is_retried_once_and_recovers(): void
    {
        Http::fake([self::BASE.'/personal' => Http::sequence()
            ->push(self::LOST, 200)
            ->push(['success' => true, 'data' => ['id' => self::ERP, 'code_internet' => '911230']], 200),
        ]);

        $r = $this->service()->personal(self::ERP);

        $this->assertSame('ok', $r['state']);
        $this->assertSame('911230', $r['data']['code_internet']);
        Http::assertSentCount(2);
    }

    public function test_lost_connection_twice_gives_down_without_third_attempt(): void
    {
        Http::fake([self::BASE.'/personal' => Http::sequence()
            ->push(self::LOST, 200)
            ->push(self::LOST, 200)
            ->push(['success' => true, 'data' => ['id' => self::ERP]], 200),
        ]);

        $r = $this->service()->personal(self::ERP);

        $this->assertSame('down', $r['state']);
        $this->assertSame('lost_connection', $r['reason']);
        Http::assertSentCount(2);
    }

    public function test_lost_connection_in_pool_only_retries_that_section(): void
    {
        Http::fake([
            self::BASE.'/vouchers' => Http::sequence()->push(self::LOST, 200)->push(self::GRANT, 200),
            self::BASE.'/loyalty-points' => Http::response(['success' => true, 'data' => ['balance' => 53, 'movements' => []]]),
        ]);

        $r = $this->service()->many(self::ERP, ['vouchers' => ['vouchers'], 'points' => ['loyalty-points']]);

        $this->assertSame('blocked', $r['vouchers']['state']);
        $this->assertSame('ok', $r['points']['state']);
        $this->assertSame(53, $r['points']['data']['balance']);
        Http::assertSentCount(3);
    }

    public function test_ok_is_cached_per_section_and_params_and_forget_invalidates(): void
    {
        Http::fake([self::BASE.'/delivery-notes*' => Http::response([
            'success' => true, 'data' => [['id' => 10101961890, 'number' => '51498']],
            'pagination' => ['limit' => 10, 'offset' => 0, 'count' => 1, 'hasMore' => false],
        ])]);

        $first = $this->service()->deliveryNotes(self::ERP, ['limit' => 10]);
        $second = $this->service()->deliveryNotes(self::ERP, ['limit' => 10]);
        $this->assertFalse($first['cached']);
        $this->assertTrue($second['cached']);
        $this->assertSame(['limit' => 10, 'offset' => 0, 'count' => 1, 'has_more' => false], $second['pagination']);
        Http::assertSentCount(1);

        $this->service()->deliveryNotes(self::ERP, ['limit' => 20]);
        Http::assertSentCount(2);

        $this->service()->forgetCustomer(self::ERP);
        $this->service()->deliveryNotes(self::ERP, ['limit' => 10]);
        Http::assertSentCount(3);
    }

    public function test_detail_of_another_customer_is_unavailable(): void
    {
        Http::fake([self::BASE.'/delivery-notes/10101961890' => Http::response(['success' => true, 'data' => ['id' => 999, 'lines' => []]])]);

        $r = $this->service()->deliveryNoteDetail(self::ERP, 10101961890);

        $this->assertSame('unavailable', $r['state']);
        $this->assertSame('foreign', $r['reason']);
    }

    public function test_circuit_opens_after_repeated_connection_failures(): void
    {
        Http::fake([self::BASE.'/catalogs' => Http::failedConnection()]);

        // Los "down" se cachean 30 s: se fuerza la lectura para acumular fallos.
        for ($i = 0; $i < 3; $i++) {
            $this->service()->catalogs(self::ERP, fresh: true);
        }
        Http::assertSentCount(3);

        $r = $this->service()->catalogs(self::ERP, fresh: true);

        $this->assertSame('down', $r['state']);
        $this->assertSame('circuit_open', $r['reason']);
        Http::assertSentCount(3);
    }

    public function test_query_params_are_whitelisted_per_section(): void
    {
        Http::fake([self::BASE.'/orders*' => Http::response(['success' => true, 'data' => [], 'pagination' => []])]);

        $this->service()->orders(self::ERP, ['limit' => 5, 'status' => '7', 'from' => '2025-01-01', 'evil' => 'x']);

        Http::assertSent(function (Request $request): bool {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $q);

            return str_starts_with($request->url(), self::BASE.'/orders?')
                && $q === ['from' => '2025-01-01', 'limit' => '5', 'status' => '7'];
        });
    }
}
