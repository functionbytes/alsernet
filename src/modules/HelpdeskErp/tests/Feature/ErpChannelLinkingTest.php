<?php

namespace Modules\HelpdeskErp\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Laravel\Pulse\Pulse;
use Modules\Helpdesk\Events\ConversationCreated;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskErp\Jobs\LinkCustomerToErpJob;
use Modules\HelpdeskErp\Services\ErpCustomerLinkerService;
use Tests\TestCase;

/**
 * Tests de vinculación ERP para todos los canales de contacto.
 *
 * Cubre: email, WhatsApp, Facebook (PSID), Instagram, web chat, PrestaShop.
 * Usa Http::fake() para simular el manager Oracle — no necesita conexión real.
 *
 * Los emails de cliente se generan con UUID para evitar colisiones de unique key
 * entre ejecuciones del test suite, independientemente del estado del DB.
 */
class ErpChannelLinkingTest extends TestCase
{
    use DatabaseTransactions;

    /** Envuelve ambas conexiones en transacción para rollback entre tests. */
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

    /* ── Canal: Email ─────────────────────────────────────────────────────── */

    public function test_email_customer_is_linked_to_erp_by_email(): void
    {
        $email = $this->uniqueEmail();
        $id = $this->uniqueErpId();

        Http::fake([
            '*/erp/customer/search*' => Http::response([
                'data' => [
                    ['id' => $id, 'label' => 'Ana', 'surnames' => 'López', 'email' => $email, 'cif' => ''],
                ],
            ]),
        ]);

        $customer = Customer::factory()->create([
            'email' => $email,
            'phone' => null,
            'whatsapp_phone' => null,
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

    public function test_email_match_is_case_insensitive(): void
    {
        $email = $this->uniqueEmail();
        $emailUpper = strtoupper($email);
        $id = $this->uniqueErpId();

        Http::fake([
            '*/erp/customer/search*' => Http::response([
                'data' => [
                    ['id' => $id, 'label' => 'Bea', 'surnames' => 'Ruiz', 'email' => $emailUpper, 'cif' => ''],
                ],
            ]),
        ]);

        $customer = Customer::factory()->create([
            'email' => $email,
            'phone' => null,
            'whatsapp_phone' => null,
        ]);
        $customer->load('externalIds');

        $erpId = app(ErpCustomerLinkerService::class)->linkCustomer($customer);

        $this->assertSame($id, $erpId);
    }

    public function test_email_not_found_in_erp_returns_null(): void
    {
        Http::fake([
            '*/erp/customer/search*' => Http::response(['data' => []]),
        ]);

        $customer = Customer::factory()->create([
            'email' => $this->uniqueEmail(),
            'phone' => null,
            'whatsapp_phone' => null,
        ]);
        $customer->load('externalIds');

        $erpId = app(ErpCustomerLinkerService::class)->linkCustomer($customer);

        $this->assertNull($erpId);
        $this->assertDatabaseMissing('helpdesk_customer_external_ids', [
            'customer_id' => $customer->id,
            'platform' => 'erp',
        ], 'helpdesk');
    }

    public function test_manager_connection_failure_does_not_abort_the_fallback_chain(): void
    {
        // La búsqueda por email falla (manager caído/timeout); linkCustomer()
        // debe seguir probando la estrategia de teléfono en vez de abortar todo
        // el job — searchCustomers() ahora propaga los fallos de conexión.
        $id = $this->uniqueErpId();
        $email = $this->uniqueEmail();
        $calls = 0;

        Http::fake(function () use (&$calls, $id) {
            $calls++;
            if ($calls === 1) {
                throw new ConnectionException('down');
            }

            return Http::response([
                'data' => [
                    ['id' => $id, 'label' => 'Dana', 'surnames' => 'Ortiz', 'email' => '', 'cif' => ''],
                ],
            ]);
        });

        $customer = Customer::factory()->create([
            'email' => $email,
            'phone' => null,
            'whatsapp_phone' => '+34 666 987 654',
        ]);
        $customer->load('externalIds');

        $erpId = app(ErpCustomerLinkerService::class)->linkCustomer($customer);

        $this->assertSame($id, $erpId);
        $this->assertDatabaseHas('helpdesk_customer_external_ids', [
            'customer_id' => $customer->id,
            'platform' => 'erp',
            'external_id' => (string) $id,
            'metadata->linked_via' => 'phone',
        ], 'helpdesk');
    }

    /* ── Canal: WhatsApp ──────────────────────────────────────────────────── */

    public function test_whatsapp_customer_is_linked_by_phone_digits(): void
    {
        $id = $this->uniqueErpId();

        // WhatsApp solo tiene teléfono, sin email verificado
        Http::fake([
            '*/erp/customer/search*' => Http::response([
                'data' => [
                    ['id' => $id, 'label' => 'Carlos', 'surnames' => 'Martín', 'email' => '', 'cif' => ''],
                ],
            ]),
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

        // La búsqueda debe hacerse con dígitos puros (sin prefijo de país)
        Http::assertSentCount(1);
        $sentUrl = Http::recorded()[0][0]->url();
        $this->assertStringContainsString('666123456', $sentUrl);
    }

    public function test_whatsapp_with_international_format_strips_country_code(): void
    {
        $id = $this->uniqueErpId();

        Http::fake([
            '*/erp/customer/search*' => Http::response([
                'data' => [['id' => $id, 'label' => 'Diana', 'surnames' => '', 'email' => '', 'cif' => '']],
            ]),
        ]);

        $customer = Customer::factory()->create([
            'email' => $this->anonymousEmail('wa'),
            'phone' => null,
            'whatsapp_phone' => '0034 600 000 001',
        ]);
        $customer->load('externalIds');

        $erpId = app(ErpCustomerLinkerService::class)->linkCustomer($customer);

        $this->assertSame($id, $erpId);
        $sentUrl = Http::recorded()[0][0]->url();
        $this->assertStringContainsString('600000001', $sentUrl);
    }

    /* ── Canal: Facebook Messenger ────────────────────────────────────────── */

    public function test_facebook_customer_without_email_cannot_be_auto_linked(): void
    {
        Http::fake([
            '*/erp/customer/search*' => Http::response(['data' => []]),
        ]);

        // Facebook solo tiene PSID — sin email ni teléfono
        $customer = Customer::factory()->create([
            'email' => $this->anonymousEmail('fb'),
            'phone' => null,
            'whatsapp_phone' => null,
            'facebook_psid' => '123456789012345',
        ]);
        $customer->load('externalIds');

        $erpId = app(ErpCustomerLinkerService::class)->linkCustomer($customer);

        $this->assertNull($erpId);
        $this->assertDatabaseMissing('helpdesk_customer_external_ids', [
            'customer_id' => $customer->id,
            'platform' => 'erp',
        ], 'helpdesk');
    }

    public function test_facebook_customer_with_known_email_is_linked(): void
    {
        $email = $this->uniqueEmail();
        $id = $this->uniqueErpId();

        Http::fake([
            '*/erp/customer/search*' => Http::response([
                'data' => [['id' => $id, 'label' => 'Eva', 'surnames' => '', 'email' => $email, 'cif' => '']],
            ]),
        ]);

        $customer = Customer::factory()->create([
            'email' => $email,
            'facebook_psid' => '987654321',
            'phone' => null,
            'whatsapp_phone' => null,
        ]);
        $customer->load('externalIds');

        $erpId = app(ErpCustomerLinkerService::class)->linkCustomer($customer);

        $this->assertSame($id, $erpId);
    }

    /* ── Canal: Instagram ─────────────────────────────────────────────────── */

    public function test_instagram_customer_without_email_cannot_be_auto_linked(): void
    {
        Http::fake([
            '*/erp/customer/search*' => Http::response(['data' => []]),
        ]);

        $customer = Customer::factory()->create([
            'email' => $this->anonymousEmail('ig'),
            'phone' => null,
            'whatsapp_phone' => null,
            'instagram_id' => 'ig_user_'.Str::random(8),
        ]);
        $customer->load('externalIds');

        $erpId = app(ErpCustomerLinkerService::class)->linkCustomer($customer);

        $this->assertNull($erpId);
    }

    public function test_instagram_customer_with_known_email_is_linked(): void
    {
        $email = $this->uniqueEmail();
        $id = $this->uniqueErpId();

        Http::fake([
            '*/erp/customer/search*' => Http::response([
                'data' => [['id' => $id, 'label' => 'Fran', 'surnames' => '', 'email' => $email, 'cif' => '']],
            ]),
        ]);

        $customer = Customer::factory()->create([
            'email' => $email,
            'instagram_id' => 'fran_'.Str::random(6),
            'phone' => null,
            'whatsapp_phone' => null,
        ]);
        $customer->load('externalIds');

        $erpId = app(ErpCustomerLinkerService::class)->linkCustomer($customer);

        $this->assertSame($id, $erpId);
    }

    /* ── Canal: Web chat / Soporte web ───────────────────────────────────── */

    public function test_web_customer_with_email_is_linked(): void
    {
        $email = $this->uniqueEmail();
        $id = $this->uniqueErpId();

        Http::fake([
            '*/erp/customer/search*' => Http::response([
                'data' => [['id' => $id, 'label' => 'Gema', 'surnames' => 'Torres', 'email' => $email, 'cif' => '']],
            ]),
        ]);

        $customer = Customer::factory()->create([
            'email' => $email,
            'phone' => '611 222 333',
            'whatsapp_phone' => null,
        ]);
        $customer->load('externalIds');

        $erpId = app(ErpCustomerLinkerService::class)->linkCustomer($customer);

        $this->assertSame($id, $erpId);
    }

    public function test_web_anonymous_customer_without_phone_cannot_be_linked(): void
    {
        Http::fake();

        $customer = Customer::factory()->create([
            'email' => $this->anonymousEmail('web'),
            'phone' => null,
            'whatsapp_phone' => null,
        ]);
        $customer->load('externalIds');

        $erpId = app(ErpCustomerLinkerService::class)->linkCustomer($customer);

        $this->assertNull($erpId);
        Http::assertNothingSent();
    }

    public function test_web_anonymous_customer_with_phone_falls_back_to_phone_search(): void
    {
        $id = $this->uniqueErpId();

        Http::fake([
            '*/erp/customer/search*' => Http::response([
                'data' => [['id' => $id, 'label' => 'Hugo', 'surnames' => '', 'email' => '', 'cif' => '']],
            ]),
        ]);

        $customer = Customer::factory()->create([
            'email' => $this->anonymousEmail('web'),
            'phone' => '+34 622 000 000',
            'whatsapp_phone' => null,
        ]);
        $customer->load('externalIds');

        $erpId = app(ErpCustomerLinkerService::class)->linkCustomer($customer);

        $this->assertSame($id, $erpId);
    }

    /* ── Canal: PrestaShop ────────────────────────────────────────────────── */

    public function test_prestashop_customer_linked_by_email(): void
    {
        $email = $this->uniqueEmail();
        $id = $this->uniqueErpId();
        $psId = (string) $this->uniqueErpId();

        Http::fake([
            '*/erp/customer/search*' => Http::response([
                'data' => [['id' => $id, 'label' => 'Irene', 'surnames' => 'Costa', 'email' => $email, 'cif' => '']],
            ]),
        ]);

        $customer = Customer::factory()->create([
            'email' => $email,
            'phone' => null,
            'whatsapp_phone' => null,
        ]);
        $customer->linkExternalId('prestashop', $psId, ['email' => $email]);
        $customer->load('externalIds');

        $erpId = app(ErpCustomerLinkerService::class)->linkCustomer($customer);

        $this->assertSame($id, $erpId);
    }

    public function test_prestashop_anonymous_chat_customer_linked_via_ps_metadata_email(): void
    {
        $realEmail = $this->uniqueEmail();
        $id = $this->uniqueErpId();
        $psId = (string) $this->uniqueErpId();

        Http::fake([
            '*/erp/customer/search*' => Http::response([
                'data' => [['id' => $id, 'label' => 'Javier', 'surnames' => '', 'email' => $realEmail, 'cif' => '']],
            ]),
        ]);

        $customer = Customer::factory()->create([
            'email' => $this->anonymousEmail('ps'),
            'phone' => null,
            'whatsapp_phone' => null,
        ]);
        $customer->linkExternalId('prestashop', $psId, ['email' => $realEmail]);
        $customer->load('externalIds');

        $erpId = app(ErpCustomerLinkerService::class)->linkCustomer($customer);

        $this->assertSame($id, $erpId);
        $this->assertDatabaseHas('helpdesk_customer_external_ids', [
            'customer_id' => $customer->id,
            'platform' => 'erp',
            'external_id' => (string) $id,
        ], 'helpdesk');
    }

    /* ── Idempotencia ─────────────────────────────────────────────────────── */

    public function test_already_linked_customer_skips_erp_api_call(): void
    {
        Http::fake();

        $id = $this->uniqueErpId();
        $customer = Customer::factory()->create(['email' => $this->uniqueEmail()]);
        $customer->linkExternalId('erp', (string) $id, ['linked_via' => 'email']);
        $customer->load('externalIds');

        $erpId = app(ErpCustomerLinkerService::class)->linkCustomer($customer);

        $this->assertSame($id, $erpId);
        Http::assertNothingSent();
    }

    public function test_link_is_idempotent_when_called_twice(): void
    {
        $email = $this->uniqueEmail();
        $id = $this->uniqueErpId();

        Http::fake([
            '*/erp/customer/search*' => Http::response([
                'data' => [['id' => $id, 'label' => 'Karla', 'surnames' => '', 'email' => $email, 'cif' => '']],
            ]),
        ]);

        $customer = Customer::factory()->create([
            'email' => $email,
            'phone' => null,
            'whatsapp_phone' => null,
        ]);
        $customer->load('externalIds');
        $linker = app(ErpCustomerLinkerService::class);

        $linker->linkCustomer($customer);

        // Segunda llamada — debe retornar el mismo ID sin reintentar ERP
        Http::fake();
        $customer->refresh()->load('externalIds');
        $erpId = $linker->linkCustomer($customer);

        $this->assertSame($id, $erpId);
        Http::assertNothingSent();
        $this->assertSame(1, $customer->externalIds()->where('platform', 'erp')->count());
    }

    /* ── Fallback: email no encontrado → busca por teléfono ──────────────── */

    public function test_phone_fallback_when_email_not_found_in_erp(): void
    {
        $id = $this->uniqueErpId();

        Http::fake([
            '*/erp/customer/search*' => Http::sequence()
                ->push(['data' => []])                                                                   // búsqueda por email → sin resultados
                ->push(['data' => [['id' => $id, 'label' => 'Luis', 'surnames' => '', 'email' => '', 'cif' => '']]]), // por teléfono → encontrado
        ]);

        $customer = Customer::factory()->create([
            'email' => $this->uniqueEmail(),
            'phone' => '+34 655 000 000',
            'whatsapp_phone' => null,
        ]);
        $customer->load('externalIds');

        $erpId = app(ErpCustomerLinkerService::class)->linkCustomer($customer);

        $this->assertSame($id, $erpId);
        Http::assertSentCount(2);
    }

    /* ── Listener y Job ───────────────────────────────────────────────────── */

    public function test_conversation_created_event_dispatches_link_job(): void
    {
        Queue::fake();

        $customer = Customer::factory()->create(['email' => $this->uniqueEmail()]);
        $conversation = $this->makeConversation($customer);

        event(new ConversationCreated($conversation));

        Queue::assertPushed(LinkCustomerToErpJob::class, function ($job) use ($customer) {
            return $this->getJobCustomerId($job) === $customer->id;
        });
    }

    public function test_conversation_created_listener_is_registered(): void
    {
        // Verifica que el listener está registrado comprobando el comportamiento:
        // al disparar el evento, el job debe encolarse.
        Queue::fake();

        $customer = Customer::factory()->create(['email' => $this->uniqueEmail()]);
        $conversation = $this->makeConversation($customer);

        event(new ConversationCreated($conversation));

        Queue::assertPushed(LinkCustomerToErpJob::class);
    }

    public function test_link_job_saves_erp_id_in_external_ids(): void
    {
        $email = $this->uniqueEmail();
        $id = $this->uniqueErpId();

        Http::fake([
            '*/erp/customer/search*' => Http::response([
                'data' => [['id' => $id, 'label' => 'María', 'surnames' => '', 'email' => $email, 'cif' => '']],
            ]),
        ]);

        $customer = Customer::factory()->create([
            'email' => $email,
            'phone' => null,
            'whatsapp_phone' => null,
        ]);

        (new LinkCustomerToErpJob($customer->id))->handle(
            app(ErpCustomerLinkerService::class)
        );

        $this->assertDatabaseHas('helpdesk_customer_external_ids', [
            'customer_id' => $customer->id,
            'platform' => 'erp',
            'external_id' => (string) $id,
        ], 'helpdesk');
    }

    public function test_link_job_handles_nonexistent_customer_gracefully(): void
    {
        Http::fake();

        (new LinkCustomerToErpJob(999999))->handle(
            app(ErpCustomerLinkerService::class)
        );

        Http::assertNothingSent();
        $this->assertTrue(true);
    }

    /* ── Helpers privados ─────────────────────────────────────────────────── */

    /** Email único por UUID para evitar colisiones de unique key entre ejecuciones. */
    private function uniqueEmail(): string
    {
        return Str::lower(Str::replace('-', '', Str::uuid())).'@test.example';
    }

    /** ID ERP aleatorio para evitar colisiones de unique key entre ejecuciones. */
    private function uniqueErpId(): int
    {
        return random_int(1000000, 9999999);
    }

    /** Email anónimo para clientes de canales sin email real (WhatsApp, Facebook...). */
    private function anonymousEmail(string $channel): string
    {
        return 'guest-'.$channel.'-'.Str::random(8).'@anonymous.local';
    }

    private function makeConversation(Customer $customer): Conversation
    {
        return Conversation::factory()->create([
            'customer_id' => $customer->id,
        ]);
    }

    private function getJobCustomerId(LinkCustomerToErpJob $job): int
    {
        $ref = new \ReflectionProperty($job, 'customerId');

        return $ref->getValue($job);
    }
}
