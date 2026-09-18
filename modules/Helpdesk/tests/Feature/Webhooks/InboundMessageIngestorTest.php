<?php

namespace Modules\Helpdesk\Tests\Feature\Webhooks;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Services\Webhooks\InboundMessageIngestor;
use Modules\Helpdesk\Support\ChannelMetrics;
use Tests\Concerns\SeedsHelpdeskRoles;
use Tests\TestCase;

class InboundMessageIngestorTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsHelpdeskRoles;

    protected $connectionsToTransact = [null, 'helpdesk'];

    protected function setUp(): void
    {
        parent::setUp();

        // Los listeners de inbound consultan User::role('helpdesk-agent').
        $this->seedHelpdeskRoles();

        // ConversationCreated dispara LinkCustomerToErpJob (sync en tests); apuntar
        // el manager ERP a un host local que falla rápido evita esperar los 15s de
        // timeout del cURL real por cada cliente nuevo.
        config(['helpdeskErp.manager_url' => 'http://manager.test']);
    }

    /** Teléfono único por test para no colisionar con estado filtrado de otros tests. */
    private function customer(): Customer
    {
        ConversationStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1],
        );

        return Customer::factory()->create([
            'whatsapp_phone' => '521'.fake()->unique()->numberBetween(1000000000, 9999999999),
        ]);
    }

    private function ingestor(): InboundMessageIngestor
    {
        return app(InboundMessageIngestor::class);
    }

    public function test_ingest_crea_conversacion_e_item(): void
    {
        $customer = $this->customer();

        $item = $this->ingestor()->ingest('whatsapp', $customer->whatsapp_phone, $customer, [
            'body' => 'Hola',
            'external_id' => 'wamid.'.uniqid(),
        ]);

        $this->assertNotNull($item);
        $this->assertSame('Hola', $item->body);
        $this->assertDatabaseHas('helpdesk_conversations', [
            'channel' => 'whatsapp',
            'external_sender_id' => $customer->whatsapp_phone,
        ], 'helpdesk');
    }

    public function test_ingest_deduplica_por_external_id(): void
    {
        $customer = $this->customer();
        $externalId = 'wamid.'.uniqid();

        $first = $this->ingestor()->ingest('whatsapp', $customer->whatsapp_phone, $customer, ['body' => 'Hola', 'external_id' => $externalId]);
        $second = $this->ingestor()->ingest('whatsapp', $customer->whatsapp_phone, $customer, ['body' => 'Repetido', 'external_id' => $externalId]);

        $this->assertNotNull($first);
        $this->assertNull($second);
        $this->assertSame(1, ConversationItem::where('external_id', $externalId)->count());
    }

    public function test_ingest_reabre_la_ventana_de_servicio_whatsapp(): void
    {
        $customer = $this->customer();

        $item = $this->ingestor()->ingest('whatsapp', $customer->whatsapp_phone, $customer, ['body' => 'Hola', 'external_id' => 'wamid.'.uniqid()]);

        $conversation = $item->conversation()->first();
        $this->assertNotNull($conversation->last_customer_message_at);
        $this->assertTrue($conversation->isWhatsAppWindowOpen());
    }

    public function test_ingest_incrementa_la_metrica_inbound(): void
    {
        $customer = $this->customer();
        $today = now()->format('Y-m-d');
        $before = ChannelMetrics::get($today, 'whatsapp', 'inbound');

        $this->ingestor()->ingest('whatsapp', $customer->whatsapp_phone, $customer, ['body' => 'Hola', 'external_id' => 'wamid.'.uniqid()]);

        $this->assertSame($before + 1, ChannelMetrics::get($today, 'whatsapp', 'inbound'));
    }

    public function test_comando_de_metricas_corre(): void
    {
        ChannelMetrics::increment('inbound', 'whatsapp');

        $this->artisan('helpdesk:channel-metrics')->assertSuccessful();
    }
}
