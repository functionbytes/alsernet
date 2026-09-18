<?php

namespace Modules\HelpdeskErp\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laravel\Pulse\Pulse;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskErp\Services\ErpContextService;
use Modules\HelpdeskErp\Services\ErpCustomerLinkerService;
use Tests\TestCase;

/**
 * Endurecimiento del match por teléfono contra el ERP.
 *
 * La búsqueda por dígitos del manager es fuzzy (IDCLIENTE / IDTARJETA /
 * CODIGO_INTERNET además de teléfono), así que tomar results[0] a ciegas
 * podía vincular/atribuir al cliente equivocado. Criterio nuevo (el mismo que
 * el email): con varios candidatos → ambiguo, no vincular; con uno solo →
 * verificar contra su ficha (erp/customer/{id}) que el teléfono normalizado
 * coincide de verdad.
 */
class ErpPhoneMatchTest extends TestCase
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

        config(['helpdeskErp.manager_url' => 'http://manager.test']);
    }

    /* ── ErpCustomerLinkerService ─────────────────────────────────────────── */

    public function test_linker_does_not_link_when_phone_search_is_ambiguous(): void
    {
        Http::fake([
            '*/erp/customer/search*' => Http::response([
                'data' => [
                    ['id' => 111, 'label' => 'Uno', 'surnames' => '', 'email' => '', 'cif' => ''],
                    ['id' => 222, 'label' => 'Dos', 'surnames' => '', 'email' => '', 'cif' => ''],
                ],
            ]),
        ]);

        $customer = Customer::factory()->create([
            'email' => $this->anonymousEmail(),
            'phone' => null,
            'whatsapp_phone' => '+34 666 111 222',
        ]);
        $customer->load('externalIds');

        $erpId = app(ErpCustomerLinkerService::class)->linkCustomer($customer);

        $this->assertNull($erpId);
        $this->assertDatabaseMissing('helpdesk_customer_external_ids', [
            'customer_id' => $customer->id,
            'platform' => 'erp',
        ], 'helpdesk');

        // Ambiguo: ni siquiera se consulta la ficha de ningún candidato.
        Http::assertSentCount(1);
    }

    public function test_linker_does_not_link_when_candidate_phone_does_not_match(): void
    {
        // Un único resultado, pero su ficha tiene OTRO teléfono (p. ej. la
        // búsqueda por dígitos casó contra IDTARJETA / CODIGO_INTERNET).
        Http::fake([
            '*/erp/customer/search*' => Http::response([
                'data' => [
                    ['id' => 333, 'label' => 'Tres', 'surnames' => '', 'email' => '', 'cif' => ''],
                ],
            ]),
            '*/erp/customer/*' => Http::response(['data' => ['phones' => [['number' => '699999999']]]]),
        ]);

        $customer = Customer::factory()->create([
            'email' => $this->anonymousEmail(),
            'phone' => null,
            'whatsapp_phone' => '+34 666 111 222',
        ]);
        $customer->load('externalIds');

        $erpId = app(ErpCustomerLinkerService::class)->linkCustomer($customer);

        $this->assertNull($erpId);
        $this->assertDatabaseMissing('helpdesk_customer_external_ids', [
            'customer_id' => $customer->id,
            'platform' => 'erp',
        ], 'helpdesk');
    }

    public function test_linker_does_not_link_when_candidate_phone_cannot_be_verified(): void
    {
        // La ficha del candidato no responde → no verificable → no vincular.
        Http::fake([
            '*/erp/customer/search*' => Http::response([
                'data' => [
                    ['id' => 444, 'label' => 'Cuatro', 'surnames' => '', 'email' => '', 'cif' => ''],
                ],
            ]),
            '*/erp/customer/*' => Http::response(null, 500),
        ]);

        $customer = Customer::factory()->create([
            'email' => $this->anonymousEmail(),
            'phone' => null,
            'whatsapp_phone' => '+34 666 111 222',
        ]);
        $customer->load('externalIds');

        $this->assertNull(app(ErpCustomerLinkerService::class)->linkCustomer($customer));
    }

    public function test_linker_links_single_candidate_with_exact_normalized_phone(): void
    {
        Http::fake([
            '*/erp/customer/search*' => Http::response([
                'data' => [
                    ['id' => 555, 'label' => 'Cinco', 'surnames' => '', 'email' => '', 'cif' => ''],
                ],
            ]),
            // Formato distinto, mismo número tras normalizar.
            '*/erp/customer/*' => Http::response(['data' => ['phones' => [['number' => '+34 666-111-222']]]]),
        ]);

        $customer = Customer::factory()->create([
            'email' => $this->anonymousEmail(),
            'phone' => null,
            'whatsapp_phone' => '34666111222',
        ]);
        $customer->load('externalIds');

        $this->assertSame(555, app(ErpCustomerLinkerService::class)->linkCustomer($customer));
        $this->assertDatabaseHas('helpdesk_customer_external_ids', [
            'customer_id' => $customer->id,
            'platform' => 'erp',
            'external_id' => '555',
            'metadata->linked_via' => 'phone',
        ], 'helpdesk');
    }

    /* ── ErpContextService (fallback por teléfono del contexto) ───────────── */

    public function test_context_phone_fallback_returns_not_found_when_ambiguous(): void
    {
        Http::fake([
            '*/erp/customer/search*' => Http::response([
                'data' => [
                    ['id' => 111, 'label' => 'Uno', 'surnames' => '', 'email' => '', 'cif' => ''],
                    ['id' => 222, 'label' => 'Dos', 'surnames' => '', 'email' => '', 'cif' => ''],
                ],
            ]),
        ]);

        $context = app(ErpContextService::class)
            ->getCustomerContext($this->anonymousEmail(), '+34 666 111 222');

        $this->assertFalse($context['customer']['found']);
    }

    public function test_context_phone_fallback_returns_not_found_when_candidate_phone_differs(): void
    {
        Http::fake([
            '*/erp/customer/search*' => Http::response([
                'data' => [
                    ['id' => 333, 'label' => 'Tres', 'surnames' => '', 'email' => '', 'cif' => ''],
                ],
            ]),
            '*/erp/customer/*' => Http::response(['data' => ['phones' => [['number' => '699999999']]]]),
        ]);

        $context = app(ErpContextService::class)
            ->getCustomerContext($this->anonymousEmail(), '+34 666 111 222');

        $this->assertFalse($context['customer']['found']);
    }

    public function test_context_phone_fallback_attributes_single_verified_candidate(): void
    {
        $id = random_int(1000000, 9999999);

        Http::fake([
            '*/erp/customer/search*' => Http::response([
                'data' => [
                    ['id' => $id, 'label' => 'Eva', 'surnames' => 'López', 'email' => '', 'cif' => ''],
                ],
            ]),
            "*/erp/customer/{$id}/balance" => Http::response(['data' => []]),
            "*/erp/customer/{$id}/orders*" => Http::response(['data' => [], 'meta' => ['loading' => false]]),
            "*/erp/customer/{$id}/invoices*" => Http::response(['data' => []]),
            '*/erp/customer/*' => Http::response(['data' => ['phones' => [['number' => '666111222']], 'addresses' => [], 'payment_method_id' => null]]),
        ]);

        $context = app(ErpContextService::class)
            ->getCustomerContext($this->anonymousEmail(), '+34 666 111 222');

        $this->assertTrue($context['customer']['found']);
        $this->assertSame($id, $context['customer']['id']);
    }

    /* ── Helpers ──────────────────────────────────────────────────────────── */

    private function anonymousEmail(): string
    {
        return 'guest-phone-'.Str::random(10).'@anonymous.local';
    }
}
