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
use Modules\HelpdeskErp\Services\ErpInvoice\ErpInvoiceDocument;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Extensión "invoice": copia informativa de factura en PDF y serie mensual
 * facturado/cobrado. El manager se simula con Http::fake usando la forma
 * EXACTA de CustomerController::invoiceDetail (ErpInvoiceDocument::sample()).
 */
class ErpInvoicePdfTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private const ERP = '101544116';

    private const BASE = '/api/erp/customer/101544116';

    private const INVOICE = 10100874512;

    private const GRANT = ['success' => false, 'error' => 'Acceso denegado a la tabla Oracle. Solicitar GRANT SELECT al DBA.'];

    private const FINANCE = ['helpdeskerp.view', 'helpdeskerp.finance.view'];

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

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(HelpdeskErpPermissionsSeeder::class);

        // Si el cargador de routes/managers.d aún no está, se registran aquí
        // igual que lo haría él (prefijo panel/helpdesk, web + auth).
        if (! Route::has('manager.helpdesk.erp.invoice.pdf')) {
            Route::middleware(['web', 'auth'])
                ->prefix('panel/helpdesk')
                ->group(module_path('HelpdeskErp', 'routes/managers.d/invoice.php'));
            app('router')->getRoutes()->refreshNameLookups();
            app('router')->getRoutes()->refreshActionLookups();
        }
    }

    public function test_pdf_is_generated_from_invoice_detail_and_logged(): void
    {
        $this->fakeManager(invoiceOk: true);
        $customer = $this->linkedCustomer();
        $user = $this->agent(self::FINANCE);

        $response = $this->actingAs($user)->get($this->url($customer, 'invoices/'.self::INVOICE.'/pdf'));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('copia-factura-fa-51498-2025.pdf', (string) $response->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('%PDF', (string) $response->getContent());

        $this->assertTrue(Activity::query()
            ->where('log_name', 'helpdeskerp')
            ->where('event', 'invoice_pdf_downloaded')
            ->where('causer_id', $user->id)
            ->where('subject_id', $customer->id)
            ->where('properties->invoice_id', self::INVOICE)
            ->exists());
    }

    public function test_blocked_invoice_returns_409_with_message(): void
    {
        $this->fakeManager(invoiceOk: false);
        $customer = $this->linkedCustomer();
        $user = $this->agent(self::FINANCE);

        $this->actingAs($user)->getJson($this->url($customer, 'invoices/'.self::INVOICE.'/pdf'))
            ->assertStatus(409)
            ->assertJsonPath('success', false)
            ->assertJsonPath('state', 'blocked')
            ->assertJsonPath('message', 'La copia en PDF estará disponible cuando Oracle conceda el permiso de lectura de facturas.');

        $this->assertFalse(Activity::query()
            ->where('log_name', 'helpdeskerp')
            ->where('event', 'invoice_pdf_downloaded')
            ->where('causer_id', $user->id)
            ->exists());
    }

    public function test_pdf_requires_finance_permission(): void
    {
        Http::fake();
        $customer = $this->linkedCustomer();

        $this->actingAs($this->agent(['helpdeskerp.view', 'helpdeskerp.orders.view']))
            ->getJson($this->url($customer, 'invoices/'.self::INVOICE.'/pdf'))
            ->assertForbidden();

        $this->actingAs($this->agent(self::FINANCE, scoped: false))
            ->getJson($this->url($customer, 'invoices/'.self::INVOICE.'/pdf'))
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_invoice_of_another_customer_is_not_found(): void
    {
        $this->fakeManager(invoiceOk: true);
        $customer = $this->linkedCustomer();

        // El manager devuelve una factura con otro id: no pertenece a este cliente.
        $this->actingAs($this->agent(self::FINANCE))
            ->getJson($this->url($customer, 'invoices/555/pdf'))
            ->assertNotFound()
            ->assertJsonPath('state', 'unavailable');
    }

    public function test_unlinked_customer_gets_409(): void
    {
        Http::fake();
        $customer = $this->customer();

        $this->actingAs($this->agent(self::FINANCE))
            ->getJson($this->url($customer, 'invoices/'.self::INVOICE.'/pdf'))
            ->assertStatus(409)
            ->assertJsonPath('state', 'unlinked');
    }

    public function test_document_tax_breakdown_adds_up_to_the_erp_total(): void
    {
        $doc = ErpInvoiceDocument::fromDetail(ErpInvoiceDocument::sample());

        $this->assertSame('FA-51498/2025', $doc['ref']);
        $this->assertSame('11/11/2025', $doc['date']);
        $this->assertCount(4, $doc['lines']);
        $this->assertSame(148.98, $doc['totals']['total']);
        $this->assertSame(125.69, $doc['totals']['base']);

        $sum = array_sum(array_map(fn (array $t): float => $t['base'] + $t['tax_amount'] + $t['surcharge_amount'], $doc['taxes']));
        $this->assertEqualsWithDelta($doc['totals']['total'], $sum, 0.001);
        $this->assertSame([21.0, 4.0], array_column($doc['taxes'], 'tax'));
    }

    public function test_monthly_series_sums_invoice_details_and_payments(): void
    {
        $this->fakeManager(invoiceOk: true);
        $customer = $this->linkedCustomer();

        $response = $this->actingAs($this->agent(self::FINANCE))
            ->getJson($this->url($customer, 'invoices/monthly'))
            ->assertOk()
            ->assertJsonPath('state', 'ok');

        $months = collect($response->json('data.months'))->keyBy('month');
        $current = now()->format('Y-m');

        $this->assertCount(6, $months);
        $this->assertSame(148.98, (float) $months[$current]['invoiced']);
        $this->assertSame(100.0, (float) $months[$current]['collected']);
    }

    public function test_monthly_series_is_blocked_without_grant(): void
    {
        $this->fakeManager(invoiceOk: false);
        $customer = $this->linkedCustomer();

        $this->actingAs($this->agent(self::FINANCE))
            ->getJson($this->url($customer, 'invoices/monthly'))
            ->assertOk()
            ->assertJsonPath('state', 'blocked')
            ->assertJsonPath('data', null);
    }

    /* ── Fixtures ─────────────────────────────────────────────────────── */

    private function fakeManager(bool $invoiceOk): void
    {
        $sample = ErpInvoiceDocument::sample();
        $today = now()->format('Y-m-d');

        Http::fake(function (Request $request) use ($invoiceOk, $sample, $today) {
            $path = (string) parse_url($request->url(), PHP_URL_PATH);
            $sub = trim(substr($path, strlen(self::BASE)), '/');

            if (! $invoiceOk) {
                return Http::response(self::GRANT, 200);
            }

            return match (true) {
                $sub === 'invoices/'.self::INVOICE => Http::response(['success' => true, 'data' => $sample, 'meta' => ['cached' => false]]),
                str_starts_with($sub, 'invoices/') => Http::response(['success' => true, 'data' => array_merge($sample, ['id' => 999])]),
                $sub === 'invoices' => Http::response(['success' => true, 'data' => [
                    ['id' => self::INVOICE, 'series' => 'FA', 'number' => '51498', 'year' => (int) now()->format('Y'), 'date' => $today,
                        'type' => '1', 'simplified' => false, 'payment_method' => 'TARJETA', 'status' => 1],
                ], 'pagination' => ['limit' => 100, 'offset' => 0, 'count' => 1, 'hasMore' => false]]),
                $sub === 'payments' => Http::response(['success' => true, 'data' => [
                    ['id' => 1, 'payment_id' => 1, 'method' => 'TARJETA', 'amount_collected' => 100.0, 'amount_free' => 0, 'date' => $today.' 10:00:00', 'status' => 1],
                    ['id' => 2, 'payment_id' => 2, 'method' => 'TARJETA', 'amount_collected' => 50.0, 'amount_free' => 0, 'date' => $today.' 11:00:00', 'status' => 0],
                ], 'pagination' => ['limit' => 100, 'offset' => 0, 'count' => 2, 'hasMore' => false]]),
                default => Http::response(self::GRANT, 200),
            };
        });
    }

    private function url(Customer $customer, string $path): string
    {
        return '/panel/helpdesk/customers/'.$customer->id.'/erp/'.$path;
    }

    /**
     * @param  list<string>  $permissions
     */
    private function agent(array $permissions, bool $scoped = true): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo($permissions);

        if ($scoped) {
            // Atajo de CustomerPolicy::sharesInboxWith (igual que ErpChatEndpointsTest).
            $user->givePermissionTo('helpdesk.customers.manage');
        }

        return $user;
    }

    private function linkedCustomer(): Customer
    {
        $customer = $this->customer();
        $customer->linkExternalId('erp', self::ERP);

        return $customer;
    }

    private function customer(): Customer
    {
        return Customer::factory()->create([
            'email' => Str::lower(Str::replace('-', '', (string) Str::uuid())).'@test.example',
        ]);
    }
}
