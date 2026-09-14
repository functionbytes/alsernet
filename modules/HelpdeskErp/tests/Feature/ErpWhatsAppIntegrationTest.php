<?php

namespace Modules\HelpdeskErp\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Laravel\Pulse\Pulse;
use Modules\Helpdesk\Events\ConversationCreated;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskErp\Database\Seeders\HelpdeskErpPermissionsSeeder;
use Modules\HelpdeskErp\Jobs\LinkCustomerToErpJob;
use Modules\HelpdeskErp\Services\ErpCustomerLinkerService;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Tests de integración WhatsApp → ERP para el módulo HelpdeskErp.
 *
 * Cubre la normalización de teléfono, vinculación cuando el cliente existe en ERP,
 * comportamiento cuando NO existe (null, sin link), listener + job dispatching.
 * Usa Http::fake() — no necesita conexión real al manager Oracle.
 */
class ErpWhatsAppIntegrationTest extends TestCase
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

        config(['helpdeskerp.manager_url' => 'http://manager.test']);
    }

    /* ── Grupo 1: Normalización de teléfono ──────────────────────────────── */

    public function test_whatsapp_phone_with_country_prefix_is_normalized_for_erp_search(): void
    {
        $id = $this->uniqueErpId();

        Http::fake([
            '*/erp/customer/search*' => Http::response([
                'data' => [
                    ['id' => $id, 'label' => 'Ana', 'surnames' => 'García', 'email' => '', 'cif' => ''],
                ],
            ]),
            // Verificación del candidato: su ficha debe contener el teléfono buscado.
            '*/erp/customer/*' => Http::response(['data' => ['phones' => [['number' => '+34 666 123 456']]]]),
        ]);

        $customer = Customer::factory()->create([
            'email' => $this->anonymousEmail('wa'),
            'phone' => null,
            'whatsapp_phone' => '+34 666 123 456',
        ]);
        $customer->load('externalIds');

        app(ErpCustomerLinkerService::class)->linkCustomer($customer);

        // 1ª llamada: búsqueda; 2ª: verificación de teléfono contra la ficha.
        Http::assertSentCount(2);
        $sentUrl = Http::recorded()[0][0]->url();
        $this->assertStringContainsString('666123456', $sentUrl);
        $this->assertStringNotContainsString('+34', $sentUrl);
    }

    public function test_whatsapp_phone_without_prefix_searches_erp_directly(): void
    {
        $id = $this->uniqueErpId();

        Http::fake([
            '*/erp/customer/search*' => Http::response([
                'data' => [
                    ['id' => $id, 'label' => 'Bea', 'surnames' => 'Ruiz', 'email' => '', 'cif' => ''],
                ],
            ]),
            '*/erp/customer/*' => Http::response(['data' => ['phones' => [['number' => '666123456']]]]),
        ]);

        $customer = Customer::factory()->create([
            'email' => $this->anonymousEmail('wa'),
            'phone' => null,
            'whatsapp_phone' => '666123456',
        ]);
        $customer->load('externalIds');

        app(ErpCustomerLinkerService::class)->linkCustomer($customer);

        Http::assertSentCount(2);
        $sentUrl = Http::recorded()[0][0]->url();
        $this->assertStringContainsString('666123456', $sentUrl);
    }

    /* ── Grupo 2: Cliente ENCONTRADO en ERP ──────────────────────────────── */

    public function test_whatsapp_phone_found_in_erp_saves_external_id_link(): void
    {
        $id = $this->uniqueErpId();

        Http::fake([
            '*/erp/customer/search*' => Http::response([
                'data' => [
                    ['id' => $id, 'label' => 'Carlos', 'surnames' => 'Martín', 'email' => '', 'cif' => ''],
                ],
            ]),
            '*/erp/customer/*' => Http::response(['data' => ['phones' => [['number' => '666123456']]]]),
        ]);

        $customer = Customer::factory()->create([
            'email' => $this->anonymousEmail('wa'),
            'phone' => null,
            'whatsapp_phone' => '+34 666 123 456',
        ]);
        $customer->load('externalIds');

        $erpId = app(ErpCustomerLinkerService::class)->linkCustomer($customer);

        $this->assertSame($id, $erpId);
        $this->assertDatabaseHas('helpdesk_customer_external_ids', [
            'customer_id' => $customer->id,
            'platform' => 'erp',
            'external_id' => (string) $id,
        ], 'helpdesk');
    }

    public function test_link_metadata_contains_linked_via_phone(): void
    {
        $id = $this->uniqueErpId();

        Http::fake([
            '*/erp/customer/search*' => Http::response([
                'data' => [
                    ['id' => $id, 'label' => 'Diana', 'surnames' => '', 'email' => '', 'cif' => ''],
                ],
            ]),
            '*/erp/customer/*' => Http::response(['data' => ['phones' => [['number' => '677000001']]]]),
        ]);

        $customer = Customer::factory()->create([
            'email' => $this->anonymousEmail('wa'),
            'phone' => null,
            'whatsapp_phone' => '+34 677 000 001',
        ]);
        $customer->load('externalIds');

        app(ErpCustomerLinkerService::class)->linkCustomer($customer);

        $link = $customer->externalIds()->where('platform', 'erp')->first();
        $this->assertNotNull($link);
        $this->assertSame('phone', $link->metadata['linked_via']);
    }

    public function test_erp_context_returns_customer_data_when_searched_by_whatsapp_phone(): void
    {
        $id = $this->uniqueErpId();
        $email = $this->anonymousEmail('wa');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(HelpdeskErpPermissionsSeeder::class);
        $user = User::factory()->create();
        $user->givePermissionTo('helpdeskerp.view');

        Http::fake([
            '*/erp/customer/search*' => Http::response([
                'data' => [
                    ['id' => $id, 'label' => 'Eva', 'surnames' => 'López', 'email' => '', 'cif' => ''],
                ],
            ]),
            "*/erp/customer/{$id}" => Http::response(['data' => ['phones' => [['number' => '666123456']], 'addresses' => [], 'payment_method_id' => null]]),
            "*/erp/customer/{$id}/balance" => Http::response(['data' => []]),
            "*/erp/customer/{$id}/orders*" => Http::response(['data' => [], 'meta' => ['loading' => false]]),
            "*/erp/customer/{$id}/invoices*" => Http::response(['data' => []]),
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/helpdeskErp/customers/{$email}/context?phone=666123456")
            ->assertOk()
            ->assertJsonPath('customer.found', true)
            ->assertJsonPath('customer.id', $id);
    }

    /* ── Grupo 3: Cliente NO encontrado en ERP ───────────────────────────── */

    public function test_whatsapp_phone_not_in_erp_returns_null_and_creates_no_link(): void
    {
        Http::fake([
            '*/erp/customer/search*' => Http::response(['data' => []]),
        ]);

        $customer = Customer::factory()->create([
            'email' => $this->anonymousEmail('wa'),
            'phone' => null,
            'whatsapp_phone' => '+34 699 000 000',
        ]);
        $customer->load('externalIds');

        $erpId = app(ErpCustomerLinkerService::class)->linkCustomer($customer);

        $this->assertNull($erpId);
        $this->assertDatabaseMissing('helpdesk_customer_external_ids', [
            'customer_id' => $customer->id,
            'platform' => 'erp',
        ], 'helpdesk');
    }

    public function test_erp_context_returns_not_found_when_phone_has_no_erp_match(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(HelpdeskErpPermissionsSeeder::class);
        $user = User::factory()->create();
        $user->givePermissionTo('helpdeskerp.view');

        $email = $this->anonymousEmail('wa');

        Http::fake([
            '*/erp/customer/search*' => Http::response(['data' => []]),
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/helpdeskErp/customers/{$email}/context?phone=699999999")
            ->assertOk()
            ->assertJsonPath('customer.found', false);
    }

    public function test_link_job_handles_customer_not_in_erp_gracefully(): void
    {
        Http::fake([
            '*/erp/customer/search*' => Http::response(['data' => []]),
        ]);

        $customer = Customer::factory()->create([
            'email' => $this->anonymousEmail('wa'),
            'phone' => null,
            'whatsapp_phone' => '+34 611 000 000',
        ]);

        (new LinkCustomerToErpJob($customer->id))->handle(
            app(ErpCustomerLinkerService::class)
        );

        // No exception thrown — job exits cleanly
        $this->assertDatabaseMissing('helpdesk_customer_external_ids', [
            'customer_id' => $customer->id,
            'platform' => 'erp',
        ], 'helpdesk');
    }

    /* ── Grupo 4: Listener + Job dispatching ─────────────────────────────── */

    public function test_conversation_created_event_dispatches_link_job_for_whatsapp_customer(): void
    {
        Queue::fake();

        $customer = Customer::factory()->create([
            'email' => $this->anonymousEmail('wa'),
            'whatsapp_phone' => '+34 666 123 456',
        ]);
        $conversation = Conversation::factory()->create(['customer_id' => $customer->id]);

        event(new ConversationCreated($conversation));

        Queue::assertPushed(LinkCustomerToErpJob::class, function (LinkCustomerToErpJob $job) use ($customer) {
            return $this->getJobCustomerId($job) === $customer->id;
        });
    }

    public function test_reusing_existing_erp_link_skips_api_call(): void
    {
        Http::fake();

        $id = $this->uniqueErpId();
        $customer = Customer::factory()->create([
            'email' => $this->anonymousEmail('wa'),
            'whatsapp_phone' => '+34 666 123 456',
        ]);
        $customer->linkExternalId('erp', (string) $id, ['linked_via' => 'phone']);
        $customer->load('externalIds');

        $erpId = app(ErpCustomerLinkerService::class)->linkCustomer($customer);

        $this->assertSame($id, $erpId);
        Http::assertNothingSent();
    }

    /* ── Helpers privados ────────────────────────────────────────────────── */

    private function uniqueErpId(): int
    {
        return random_int(1000000, 9999999);
    }

    private function anonymousEmail(string $channel): string
    {
        return 'guest-'.$channel.'-'.Str::random(8).'@anonymous.local';
    }

    private function getJobCustomerId(LinkCustomerToErpJob $job): int
    {
        $ref = new \ReflectionProperty($job, 'customerId');

        return $ref->getValue($job);
    }
}
