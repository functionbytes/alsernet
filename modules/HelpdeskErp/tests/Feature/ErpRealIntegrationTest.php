<?php

namespace Modules\HelpdeskErp\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Laravel\Pulse\Pulse;
use Modules\Helpdesk\Events\ConversationCreated;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskErp\Jobs\LinkCustomerToErpJob;
use Modules\HelpdeskErp\Services\ErpContextService;
use Modules\HelpdeskErp\Services\ErpCustomerLinkerService;
use Tests\TestCase;

/**
 * Tests de integración REAL contra el ERP Oracle (DEVELOPER schema) vía manager.
 *
 * A diferencia de ErpChannelLinkingTest (que usa Http::fake), este test:
 *   - Consulta el manager real configurado en ERP_MANAGER_URL
 *   - Usa identificadores de un cliente Oracle real (email, teléfono, CIF)
 *   - Verifica que el IDCLIENTE devuelto corresponde realmente al cliente
 *   - Cubre el flujo end-to-end: conversación entra por canal X → job se encola
 *     → linker consulta ERP → IDCLIENTE se persiste en helpdesk_customer_external_ids
 *
 * Auto-skip si:
 *   - ERP_MANAGER_URL no está configurado
 *   - El manager no responde (network / Docker apagado)
 *   - Oracle responde con error TNS / connection (manager no puede llegar a la BD)
 *   - ERP_TEST_REAL_EMAIL no está definido (no hay cliente conocido para probar)
 *
 * Para habilitar localmente, añadir al .env:
 *   ERP_TEST_REAL_EMAIL=email@de-cliente-real-en-erp.com
 *   ERP_TEST_REAL_PHONE=666123456            # opcional, dígitos puros
 *   ERP_TEST_REAL_EXPECTED_ID=12345          # opcional, IDCLIENTE esperado
 *
 * Para CI, dejar las vars vacías: los tests se marcarán como skipped.
 *
 * @group integration
 * @group erp-real
 */
class ErpRealIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = [null, 'helpdesk'];

    private ?string $realEmail = null;

    private ?string $realPhone = null;

    private ?int $expectedErpId = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Pulse stub para no contaminar métricas reales en tests
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

        $this->realEmail = env('ERP_TEST_REAL_EMAIL');
        $this->realPhone = env('ERP_TEST_REAL_PHONE');
        $expected = env('ERP_TEST_REAL_EXPECTED_ID');
        $this->expectedErpId = $expected !== null && $expected !== '' ? (int) $expected : null;

        $this->skipIfErpNotReachable();
    }

    /* ── Canal: Email — cliente real del ERP ──────────────────────────────── */

    public function test_real_email_customer_is_linked_to_oracle_id(): void
    {
        $this->skipIfMissing('ERP_TEST_REAL_EMAIL', $this->realEmail);

        $customer = Customer::factory()->create([
            'email' => $this->realEmail,
            'phone' => null,
            'whatsapp_phone' => null,
        ]);
        $customer->load('externalIds');

        $erpId = app(ErpCustomerLinkerService::class)->linkCustomer($customer);

        $this->assertNotNull(
            $erpId,
            "El email real '{$this->realEmail}' no encontró cliente en ERP. ".
            'Verifica que existe en Oracle DEVELOPER schema o ajusta ERP_TEST_REAL_EMAIL.'
        );
        $this->assertGreaterThan(0, $erpId);

        if ($this->expectedErpId !== null) {
            $this->assertSame(
                $this->expectedErpId,
                $erpId,
                "ERP devolvió IDCLIENTE {$erpId} pero ERP_TEST_REAL_EXPECTED_ID={$this->expectedErpId}"
            );
        }

        $this->assertDatabaseHas('helpdesk_customer_external_ids', [
            'customer_id' => $customer->id,
            'platform' => 'erp',
            'external_id' => (string) $erpId,
        ], 'helpdesk');
    }

    /* ── Canal: WhatsApp — cliente real del ERP por teléfono ─────────────── */

    public function test_real_whatsapp_customer_is_linked_by_phone(): void
    {
        $this->skipIfMissing('ERP_TEST_REAL_PHONE', $this->realPhone);

        $customer = Customer::factory()->create([
            'email' => 'guest-wa-'.Str::random(8).'@anonymous.local',
            'phone' => null,
            'whatsapp_phone' => $this->realPhone,
        ]);
        $customer->load('externalIds');

        $erpId = app(ErpCustomerLinkerService::class)->linkCustomer($customer);

        $this->assertNotNull(
            $erpId,
            "El teléfono real '{$this->realPhone}' no encontró cliente en ERP."
        );

        if ($this->expectedErpId !== null) {
            $this->assertSame($this->expectedErpId, $erpId);
        }

        $this->assertDatabaseHas('helpdesk_customer_external_ids', [
            'customer_id' => $customer->id,
            'platform' => 'erp',
            'external_id' => (string) $erpId,
        ], 'helpdesk');
    }

    /* ── Email inexistente — debe retornar null sin romper ───────────────── */

    public function test_real_unknown_email_returns_null_without_linking(): void
    {
        $bogusEmail = 'no-existe-jamas-'.Str::random(16).'@inexistente.test';

        $customer = Customer::factory()->create([
            'email' => $bogusEmail,
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

    /* ── Datos completos del ERP traídos por ErpContextService ───────────── */

    public function test_real_customer_context_loads_oracle_data(): void
    {
        $this->skipIfMissing('ERP_TEST_REAL_EMAIL', $this->realEmail);

        $context = app(ErpContextService::class)->getCustomerContext($this->realEmail);

        $this->assertTrue(
            $context['customer']['found'] ?? false,
            "Cliente con email real no encontrado en contexto ERP (email: {$this->realEmail})"
        );
        $this->assertArrayHasKey('id', $context['customer']);
        $this->assertArrayHasKey('name', $context['customer']);
        $this->assertIsArray($context['orders']);
        $this->assertIsArray($context['invoices']);
    }

    /* ── End-to-end: conversación → evento → job → vinculación ───────────── */

    public function test_real_conversation_pipeline_links_customer_to_erp(): void
    {
        $this->skipIfMissing('ERP_TEST_REAL_EMAIL', $this->realEmail);

        // 1. Cliente entra por un canal (email)
        $customer = Customer::factory()->create([
            'email' => $this->realEmail,
            'phone' => null,
            'whatsapp_phone' => null,
        ]);

        $conversation = Conversation::factory()->create([
            'customer_id' => $customer->id,
        ]);

        // 2. Capturamos el job sin ejecutarlo, para verificar la dispatch chain
        Queue::fake();
        event(new ConversationCreated($conversation));
        Queue::assertPushed(LinkCustomerToErpJob::class);

        // 3. Ahora ejecutamos el job de verdad y validamos el efecto final
        (new LinkCustomerToErpJob($customer->id))->handle(
            app(ErpCustomerLinkerService::class)
        );

        $erpLink = $customer->refresh()->externalIds->firstWhere('platform', 'erp');
        $this->assertNotNull($erpLink, 'El job no persistió el vínculo ERP');
        $this->assertNotEmpty($erpLink->external_id);
        $this->assertGreaterThan(0, (int) $erpLink->external_id);
    }

    /* ── Helpers ──────────────────────────────────────────────────────────── */

    private function skipIfErpNotReachable(): void
    {
        $url = (string) config('helpdeskErp.manager_url');

        if ($url === '') {
            $this->markTestSkipped('ERP_MANAGER_URL no configurado.');
        }

        // Health check rápido — si Oracle no responde con datos (TNS, conexión, 5xx),
        // los tests no tienen sentido: skipear en lugar de fallar.
        try {
            // Timeout generoso para tolerar cold-start de Oracle (primera conexión OCI8 en el worker)
            $resp = Http::timeout(15)
                ->acceptJson()
                ->get(rtrim($url, '/').'/api/erp/customer/search', [
                    'q' => '__healthcheck__',
                    'limit' => 1,
                ]);

            if ($resp->status() >= 500) {
                $this->markTestSkipped(
                    "Manager respondió {$resp->status()} — Oracle no reachable. ".
                    'Body: '.Str::limit((string) $resp->body(), 200)
                );
            }

            if (! $resp->successful()) {
                $this->markTestSkipped("Manager respondió HTTP {$resp->status()}.");
            }
        } catch (\Throwable $e) {
            $this->markTestSkipped('Manager no reachable: '.$e->getMessage());
        }
    }

    private function skipIfMissing(string $envVar, ?string $value): void
    {
        if ($value === null || $value === '') {
            $this->markTestSkipped(
                "Define {$envVar} en .env con un valor real del ERP para activar este test."
            );
        }
    }
}
