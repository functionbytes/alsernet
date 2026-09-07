<?php

namespace Modules\HelpdeskErp\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laravel\Pulse\Pulse;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskErp\Events\CustomerErpResolved;
use Modules\HelpdeskErp\Jobs\LinkCustomerToErpJob;
use Modules\HelpdeskErp\Services\ErpContextService;
use Modules\HelpdeskErp\Services\ErpCustomerLinkerService;
use Tests\TestCase;

/**
 * Estado de la búsqueda del cliente en el ERP: lo que antes se perdía en un
 * Log::info.
 *
 * Cubre las tres cosas que hacen falta para que el agente vea "sin cliente en
 * gestión" y para que el ERP no reciba la misma pregunta en cada correo:
 * el estado persistido, la entrada de auditoría, y el enfriamiento.
 */
class ErpLookupStateTest extends TestCase
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

    /* ── Estado persistido ────────────────────────────────────────────────── */

    public function test_customer_found_in_erp_is_marked_as_linked(): void
    {
        $email = $this->uniqueEmail();
        $erpId = $this->uniqueErpId();

        Http::fake([
            '*/erp/customer/search*' => Http::response([
                'data' => [['id' => $erpId, 'label' => 'Ana', 'surnames' => '', 'email' => $email, 'cif' => '']],
            ]),
        ]);

        $customer = $this->customerWithEmail($email);

        app(ErpCustomerLinkerService::class)->linkCustomer($customer);

        $customer->refresh();

        $this->assertSame('linked', $customer->erp_lookup_status);
        $this->assertNotNull($customer->erp_lookup_at);
        $this->assertFalse($customer->erpLookupFailed());
    }

    public function test_customer_missing_from_erp_is_marked_as_not_found(): void
    {
        Http::fake(['*/erp/customer/search*' => Http::response(['data' => []])]);

        $customer = $this->customerWithEmail($this->uniqueEmail());

        $this->assertNull(app(ErpCustomerLinkerService::class)->linkCustomer($customer));

        $customer->refresh();

        $this->assertSame('not_found', $customer->erp_lookup_status);
        $this->assertTrue($customer->erpLookupFailed());
    }

    /**
     * "El cliente no está en el ERP" y "el ERP no contestó" son cosas
     * distintas: la segunda merece un reintento mucho antes.
     */
    public function test_erp_outage_is_marked_as_error_not_as_missing(): void
    {
        Http::fake(['*/erp/customer/search*' => fn () => throw new ConnectionException('timeout')]);

        $customer = $this->customerWithEmail($this->uniqueEmail());

        $this->assertNull(app(ErpCustomerLinkerService::class)->linkCustomer($customer));

        $customer->refresh();

        $this->assertSame('error', $customer->erp_lookup_status);
        $this->assertTrue($customer->erpLookupFailed());
    }

    /* ── Auditoría ────────────────────────────────────────────────────────── */

    public function test_failed_lookup_is_written_to_the_integration_audit_log(): void
    {
        Http::fake(['*/erp/customer/search*' => Http::response(['data' => []])]);

        $customer = $this->customerWithEmail($this->uniqueEmail());

        app(ErpCustomerLinkerService::class)->linkCustomer($customer);

        $this->assertDatabaseHas('helpdesk_integration_audit_log', [
            'customer_id' => $customer->id,
            'platform' => 'erp',
            'action' => 'link_failed',
        ], 'helpdesk');
    }

    /**
     * Hasta ahora los vínculos automáticos no aparecían en el historial de
     * integraciones y los manuales sí, porque el linker escribía directo en
     * lugar de pasar por CustomerIntegrationService.
     */
    public function test_automatic_link_is_audited_like_a_manual_one(): void
    {
        $email = $this->uniqueEmail();
        $erpId = $this->uniqueErpId();

        Http::fake([
            '*/erp/customer/search*' => Http::response([
                'data' => [['id' => $erpId, 'label' => 'Ana', 'surnames' => '', 'email' => $email, 'cif' => '']],
            ]),
        ]);

        $customer = $this->customerWithEmail($email);

        app(ErpCustomerLinkerService::class)->linkCustomer($customer);

        $this->assertDatabaseHas('helpdesk_integration_audit_log', [
            'customer_id' => $customer->id,
            'platform' => 'erp',
            'action' => 'linked',
            'external_id' => (string) $erpId,
        ], 'helpdesk');

        $this->assertDatabaseHas('helpdesk_customer_external_ids', [
            'customer_id' => $customer->id,
            'platform' => 'erp',
            'external_id' => (string) $erpId,
        ], 'helpdesk');
    }

    /**
     * getCustomerContext() cachea también los negativos (miss_ttl). Si queda un
     * "found: false" de antes de vincular, ErpFactsService lo lee un instante
     * después —CustomerErpResolved se emite justo detrás— y las reglas de
     * enrutado evalúan erp_linked=false para un cliente que sí está en gestión.
     * Reproducido en una prueba real contra Oracle el 7-sep-2026.
     */
    public function test_linking_invalidates_the_cached_erp_context(): void
    {
        $email = $this->uniqueEmail();
        $erpId = $this->uniqueErpId();

        Http::fake([
            '*/erp/customer/search*' => Http::response([
                'data' => [['id' => $erpId, 'label' => 'Ana', 'surnames' => '', 'email' => $email, 'cif' => '']],
            ]),
        ]);

        $customer = $this->customerWithEmail($email);

        $forgotten = [];

        $this->app->instance(ErpContextService::class, new class($forgotten) extends ErpContextService
        {
            /** @param array<int, string> $forgotten */
            public function __construct(public array &$forgotten) {}

            public function searchCustomers(string $query, string $type = 'email'): array
            {
                return [['id' => 4242, 'email' => $query]];
            }

            public function forgetAllFor(string $email, array $phones = []): void
            {
                $this->forgotten[] = $email;
            }
        });

        app(ErpCustomerLinkerService::class)->linkCustomer($customer);

        $this->assertContains($email, $forgotten, 'Vincular debe tirar el contexto cacheado del cliente.');
    }

    /* ── Enfriamiento ─────────────────────────────────────────────────────── */

    public function test_job_does_not_query_the_erp_again_within_the_cooldown(): void
    {
        Http::fake(['*/erp/customer/search*' => Http::response(['data' => []])]);
        config(['helpdeskErp.lookup_cooldown_minutes' => 1440]);

        $customer = $this->customerWithEmail($this->uniqueEmail());
        $customer->recordErpLookup('not_found');

        (new LinkCustomerToErpJob($customer->id))->handle(app(ErpCustomerLinkerService::class));

        Http::assertNothingSent();
    }

    public function test_job_queries_the_erp_again_once_the_cooldown_has_passed(): void
    {
        Http::fake(['*/erp/customer/search*' => Http::response(['data' => []])]);
        config(['helpdeskErp.lookup_cooldown_minutes' => 60]);

        $customer = $this->customerWithEmail($this->uniqueEmail());
        $customer->forceFill([
            'erp_lookup_status' => 'not_found',
            'erp_lookup_at' => now()->subHours(3),
        ])->saveQuietly();

        (new LinkCustomerToErpJob($customer->id))->handle(app(ErpCustomerLinkerService::class));

        Http::assertSentCount(1);
    }

    /** El reintento que pide un agente a mano ignora el enfriamiento. */
    public function test_forced_job_ignores_the_cooldown(): void
    {
        Http::fake(['*/erp/customer/search*' => Http::response(['data' => []])]);
        config(['helpdeskErp.lookup_cooldown_minutes' => 1440]);

        $customer = $this->customerWithEmail($this->uniqueEmail());
        $customer->recordErpLookup('not_found');

        (new LinkCustomerToErpJob($customer->id, null, null, force: true))
            ->handle(app(ErpCustomerLinkerService::class));

        Http::assertSentCount(1);
    }

    /* ── El evento que ordena el enrutado ─────────────────────────────────── */

    public function test_job_announces_the_result_with_its_source(): void
    {
        Event::fake([CustomerErpResolved::class]);
        Http::fake(['*/erp/customer/search*' => Http::response(['data' => []])]);

        $customer = $this->customerWithEmail($this->uniqueEmail());

        (new LinkCustomerToErpJob($customer->id, 'ticket', 4242))
            ->handle(app(ErpCustomerLinkerService::class));

        Event::assertDispatched(CustomerErpResolved::class, function (CustomerErpResolved $e) use ($customer) {
            return $e->customerId === $customer->id
                && $e->sourceType === 'ticket'
                && $e->sourceId === 4242
                && $e->status === 'not_found'
                && ! $e->wasFound();
        });
    }

    /**
     * Un cliente ya vinculado no cuesta una consulta al ERP, pero el ticket que
     * acaba de entrar sí necesita su evento: si no, se quedaría sin enrutar.
     */
    public function test_already_linked_customer_still_gets_the_event_without_calling_the_erp(): void
    {
        Event::fake([CustomerErpResolved::class]);
        Http::fake(['*/erp/customer/search*' => Http::response(['data' => []])]);

        $erpId = $this->uniqueErpId();
        $customer = $this->customerWithEmail($this->uniqueEmail());
        $customer->linkExternalId('erp', (string) $erpId, ['linked_via' => 'email']);

        (new LinkCustomerToErpJob($customer->id, 'ticket', 77))
            ->handle(app(ErpCustomerLinkerService::class));

        Http::assertNothingSent();

        Event::assertDispatched(CustomerErpResolved::class, function (CustomerErpResolved $e) use ($erpId) {
            return $e->wasFound() && $e->erpCustomerId === $erpId && $e->sourceId === 77;
        });
    }

    /**
     * El origen entra en la clave de unicidad: sin eso, dos tickets del mismo
     * cliente dentro de la ventana de 5 minutos compartirían trabajo y el
     * segundo nunca recibiría su evento.
     */
    public function test_unique_id_separates_two_sources_of_the_same_customer(): void
    {
        $first = new LinkCustomerToErpJob(7, 'ticket', 1);
        $second = new LinkCustomerToErpJob(7, 'ticket', 2);

        $this->assertNotSame($first->uniqueId(), $second->uniqueId());
    }

    /* ── Helpers ──────────────────────────────────────────────────────────── */

    private function customerWithEmail(string $email): Customer
    {
        $customer = Customer::factory()->create([
            'email' => $email,
            'phone' => null,
            'whatsapp_phone' => null,
        ]);

        $customer->load('externalIds');

        return $customer;
    }

    private function uniqueEmail(): string
    {
        return Str::lower(Str::replace('-', '', Str::uuid())).'@test.example';
    }

    private function uniqueErpId(): int
    {
        return random_int(1000000, 9999999);
    }
}
