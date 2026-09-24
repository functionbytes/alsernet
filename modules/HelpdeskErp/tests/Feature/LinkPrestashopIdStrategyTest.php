<?php

namespace Modules\HelpdeskErp\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laravel\Pulse\Pulse;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskErp\Services\ErpCustomerLinkerService;
use Tests\TestCase;

/**
 * Estrategia "id de PrestaShop" del linker: en Gestión, CODIGO_INTERNET es el
 * id_customer de PrestaShop, así que un contacto vinculado a PrestaShop se
 * vincula a Gestión por GET /api/erp/customer/search/web/{psId} antes de
 * probar email o teléfono.
 */
class LinkPrestashopIdStrategyTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = [null, 'helpdesk'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(Pulse::class, new class
        {
            public function set(string $type, string $key, mixed $value, mixed $timestamp = null): object
            {
                return new \stdClass;
            }

            public function record(mixed ...$args): object
            {
                return new \stdClass;
            }
        });

        config([
            'helpdeskErp.manager_url' => 'http://manager.test',
            // El observer que encola la vinculación al crear el vínculo con
            // PrestaShop no debe correr aquí: se prueba el linker a mano.
            'helpdeskErp.link.auto_on_prestashop' => false,
        ]);
    }

    public function test_links_by_prestashop_id_before_trying_email(): void
    {
        $psId = $this->uniqueId();
        $erpId = $this->uniqueId();
        $email = $this->uniqueEmail();

        Http::fake([
            "*/erp/customer/search/web/{$psId}" => Http::response($this->webResponse($erpId, $psId)),
            '*/erp/customer/search*' => Http::response(['data' => []]),
        ]);

        $customer = $this->customerWithPrestashop($psId, $email);

        $linked = app(ErpCustomerLinkerService::class)->linkCustomer($customer);

        $this->assertSame($erpId, $linked);
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => str_contains($request->url(), "/api/erp/customer/search/web/{$psId}"));

        $link = $customer->externalIds()->where('platform', 'erp')->first();
        $this->assertNotNull($link);
        $this->assertSame((string) $erpId, (string) $link->external_id);
        $this->assertSame('prestashop_id', $link->metadata['linked_via']);
        $this->assertSame('linked', $customer->fresh()->erp_lookup_status);
    }

    public function test_deleted_erp_customer_is_not_linked_and_falls_back_to_email(): void
    {
        $psId = $this->uniqueId();
        $erpId = $this->uniqueId();
        $emailErpId = $this->uniqueId();
        $email = $this->uniqueEmail();

        Http::fake([
            "*/erp/customer/search/web/{$psId}" => Http::response($this->webResponse($erpId, $psId, ['deleted_at' => '2024-01-01', 'available' => false])),
            '*/erp/customer/search*' => Http::response([
                'data' => [['id' => $emailErpId, 'label' => 'Ana', 'surnames' => '', 'email' => $email, 'cif' => '']],
            ]),
        ]);

        $customer = $this->customerWithPrestashop($psId, $email);

        $linked = app(ErpCustomerLinkerService::class)->linkCustomer($customer);

        $this->assertSame($emailErpId, $linked);
        $link = $customer->externalIds()->where('platform', 'erp')->first();
        $this->assertSame('email', $link->metadata['linked_via']);
    }

    public function test_prestashop_id_not_in_erp_keeps_the_other_strategies(): void
    {
        $psId = $this->uniqueId();
        $erpId = $this->uniqueId();
        $email = $this->uniqueEmail();

        Http::fake([
            "*/erp/customer/search/web/{$psId}" => Http::response(['success' => true, 'exists' => false, 'data' => null]),
            '*/erp/customer/search*' => Http::response([
                'data' => [['id' => $erpId, 'label' => 'Bea', 'surnames' => '', 'email' => $email, 'cif' => '']],
            ]),
        ]);

        $customer = $this->customerWithPrestashop($psId, $email);

        $this->assertSame($erpId, app(ErpCustomerLinkerService::class)->linkCustomer($customer));
    }

    public function test_match_by_other_field_than_idweb_is_ignored(): void
    {
        $psId = $this->uniqueId();

        $response = $this->webResponse($this->uniqueId(), $psId);
        $response['matched_by'] = 'email';

        Http::fake([
            "*/erp/customer/search/web/{$psId}" => Http::response($response),
            '*/erp/customer/search*' => Http::response(['data' => []]),
        ]);

        $customer = $this->customerWithPrestashop($psId, $this->uniqueEmail());

        $this->assertNull(app(ErpCustomerLinkerService::class)->linkCustomer($customer));
        $this->assertSame('not_found', $customer->fresh()->erp_lookup_status);
    }

    public function test_manager_error_on_prestashop_lookup_is_recorded_as_error(): void
    {
        $psId = $this->uniqueId();

        Http::fake([
            "*/erp/customer/search/web/{$psId}" => Http::response(['success' => false, 'error' => 'ORA-00942'], 500),
            '*/erp/customer/search*' => Http::response(['data' => []]),
        ]);

        $customer = $this->customerWithPrestashop($psId, $this->uniqueEmail());

        $this->assertNull(app(ErpCustomerLinkerService::class)->linkCustomer($customer));
        $this->assertSame('error', $customer->fresh()->erp_lookup_status);
    }

    public function test_erp_id_already_linked_to_another_contact_is_not_reported_as_linked(): void
    {
        $psId = $this->uniqueId();
        $erpId = $this->uniqueId();

        $other = Customer::factory()->create(['email' => $this->uniqueEmail()]);
        $other->linkExternalId('erp', (string) $erpId, ['linked_via' => 'email']);

        Http::fake([
            "*/erp/customer/search/web/{$psId}" => Http::response($this->webResponse($erpId, $psId)),
            '*/erp/customer/search*' => Http::response(['data' => []]),
        ]);

        $customer = $this->customerWithPrestashop($psId, $this->uniqueEmail());

        $this->assertNull(app(ErpCustomerLinkerService::class)->linkCustomer($customer));
        $this->assertSame(0, $customer->externalIds()->where('platform', 'erp')->count());
        $this->assertNotSame('linked', $customer->fresh()->erp_lookup_status);
    }

    /* ── Helpers ─────────────────────────────────────────────────────────── */

    private function customerWithPrestashop(int $psId, string $email): Customer
    {
        $customer = Customer::factory()->create([
            'email' => $email,
            'phone' => null,
            'whatsapp_phone' => null,
        ]);
        $customer->linkExternalId('prestashop', (string) $psId, ['email' => $email]);

        return $customer->load('externalIds');
    }

    /**
     * Forma real de GET /api/erp/customer/search/web/{id} en el manager.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function webResponse(int $erpId, int $psId, array $overrides = []): array
    {
        return [
            'success' => true,
            'exists' => true,
            'matched_by' => 'idweb',
            'data' => array_merge([
                'id' => (string) $erpId,
                'label' => 'ALBERTO',
                'surnames' => 'PRUEBA',
                'cif' => null,
                'email' => null,
                'code_internet' => (string) $psId,
                'card' => null,
                'available' => true,
                'deleted_at' => null,
            ], $overrides),
        ];
    }

    private function uniqueId(): int
    {
        return random_int(900000000, 999999999);
    }

    private function uniqueEmail(): string
    {
        return 'erp-link-'.Str::lower(Str::random(12)).'@example.test';
    }
}
