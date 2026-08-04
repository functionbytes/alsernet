<?php

namespace Modules\Helpdesk\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Modules\Helpdesk\Events\ConversationCreated;
use Modules\Helpdesk\Listeners\RespondOffHoursOnConversationCreated;
use Modules\Helpdesk\Models\BusinessHour;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\OffHoursResponse;
use Modules\HelpdeskTranslate\Services\CachedTranslator;
use Tests\TestCase;

class RespondOffHoursOnConversationCreatedTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['mariadb', 'helpdesk'];

    protected function setUp(): void
    {
        parent::setUp();

        ConversationStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );

        Cache::forget('helpdesk:business_hours_open');

        // Tabla sin seeder ligado a las migraciones: puede haber una fila real
        // configurada por un admin (o dejada para pruebas manuales). Se limpia
        // dentro de la transacción del test para no depender del estado externo.
        OffHoursResponse::query()->delete();
    }

    private function closeAllBusinessHours(): void
    {
        BusinessHour::query()->update(['is_open' => false]);
    }

    private function openAllBusinessHours(): void
    {
        BusinessHour::query()->update([
            'is_open' => true,
            'opens_at' => '00:00:00',
            'closes_at' => '23:59:00',
        ]);
    }

    private function listener(): RespondOffHoursOnConversationCreated
    {
        return new RespondOffHoursOnConversationCreated;
    }

    /**
     * Cliente en 'es' == config('app.locale'): resolveCustomerLanguage()
     * devuelve 'es' sin intentar detectar/traducir nada, así los tests que no
     * prueban idioma no dependen de si hay (o no) un proveedor de traducción
     * real configurado en Settings — la BD de test es la misma BD compartida.
     */
    private function conversationWithSpanishCustomer(array $overrides = []): Conversation
    {
        return Conversation::factory()
            ->for(Customer::factory()->state(['language' => 'es']), 'customer')
            ->create($overrides);
    }

    public function test_persists_off_hours_reply_visible_in_thread_when_closed(): void
    {
        $this->closeAllBusinessHours();

        OffHoursResponse::query()->create([
            'channel' => null,
            'message' => 'Estamos fuera de horario.',
            'is_active' => true,
        ]);

        $conversation = $this->conversationWithSpanishCustomer([
            'channel' => 'web',
            'external_sender_id' => null,
        ]);

        $this->listener()->handle(new ConversationCreated($conversation));

        $item = ConversationItem::query()
            ->where('conversation_id', $conversation->id)
            ->where('metadata->auto_reply', 'off_hours')
            ->first();

        $this->assertNotNull($item, 'El auto-reply fuera de horario debe quedar persistido en el hilo.');
        $this->assertSame('Estamos fuera de horario.', $item->body);
        // Ni de agente ni de cliente: is_incoming() = false (author_id vacío),
        // mismo criterio que los mensajes del bot de ChatFlow.
        $this->assertNull($item->user_id);
        $this->assertNull($item->author_id);
    }

    public function test_prefers_channel_specific_response_over_global(): void
    {
        $this->closeAllBusinessHours();

        OffHoursResponse::query()->create(['channel' => null, 'message' => 'Global', 'is_active' => true]);
        OffHoursResponse::query()->create(['channel' => 'whatsapp', 'message' => 'WhatsApp específico', 'is_active' => true]);

        $conversation = $this->conversationWithSpanishCustomer(['channel' => 'whatsapp']);

        $this->listener()->handle(new ConversationCreated($conversation));

        $item = ConversationItem::query()->where('conversation_id', $conversation->id)->first();

        $this->assertSame('WhatsApp específico', $item->body);
    }

    public function test_prefers_language_specific_response_over_generic_and_skips_translation(): void
    {
        $this->closeAllBusinessHours();

        OffHoursResponse::query()->create(['channel' => null, 'language' => null, 'message' => 'Estamos fuera de horario.', 'is_active' => true]);
        OffHoursResponse::query()->create(['channel' => null, 'language' => 'en', 'message' => 'We are currently closed.', 'is_active' => true]);

        // Idioma ya conocido y distinto del genérico: no debería ni tocar el
        // traductor, así que un mock sin expectativas configuradas fallaría
        // la prueba si algún método llegara a invocarse.
        $this->mock(CachedTranslator::class);

        $conversation = Conversation::factory()
            ->for(Customer::factory()->state(['language' => 'en']), 'customer')
            ->create(['channel' => 'whatsapp']);

        $this->listener()->handle(new ConversationCreated($conversation));

        $item = ConversationItem::query()->where('conversation_id', $conversation->id)->first();

        $this->assertSame('We are currently closed.', $item->body);
    }

    public function test_translates_generic_message_when_customer_language_is_known(): void
    {
        $this->closeAllBusinessHours();

        OffHoursResponse::query()->create(['channel' => null, 'message' => 'Estamos fuera de horario.', 'is_active' => true]);

        $this->mock(CachedTranslator::class, function ($mock) {
            $mock->shouldReceive('translate')
                ->once()
                ->with('Estamos fuera de horario.', 'en', 'es', 'auto_outgoing')
                ->andReturn('We are currently closed.');
        });

        $conversation = Conversation::factory()
            ->for(Customer::factory()->state(['language' => 'en']), 'customer')
            ->create(['channel' => 'web']);

        $this->listener()->handle(new ConversationCreated($conversation));

        $item = ConversationItem::query()->where('conversation_id', $conversation->id)->first();

        $this->assertSame('We are currently closed.', $item->body);
    }

    public function test_detects_language_from_first_message_when_customer_language_is_unknown(): void
    {
        $this->closeAllBusinessHours();

        OffHoursResponse::query()->create(['channel' => null, 'message' => 'Estamos fuera de horario.', 'is_active' => true]);

        // Cliente en 'es' (valor de fábrica, tratado como "aún no detectado")
        // con un primer mensaje claramente en francés.
        $conversation = $this->conversationWithSpanishCustomer(['channel' => 'whatsapp']);
        ConversationItem::factory()->create([
            'conversation_id' => $conversation->id,
            'type' => 'message',
            'body' => 'Bonjour, jai besoin daide',
        ]);
        $conversation->load('items');

        $this->mock(CachedTranslator::class, function ($mock) {
            $mock->shouldReceive('detectLanguage')
                ->once()
                ->with('Bonjour, jai besoin daide', 'auto_outgoing')
                ->andReturn('fr');
            $mock->shouldReceive('translate')
                ->once()
                ->with('Estamos fuera de horario.', 'fr', 'es', 'auto_outgoing')
                ->andReturn('Nous sommes actuellement fermés.');
        });

        $this->listener()->handle(new ConversationCreated($conversation));

        $item = ConversationItem::query()
            ->where('conversation_id', $conversation->id)
            ->where('metadata->auto_reply', 'off_hours')
            ->first();

        $this->assertSame('Nous sommes actuellement fermés.', $item->body);
    }

    public function test_does_nothing_when_business_is_open(): void
    {
        $this->openAllBusinessHours();

        OffHoursResponse::query()->create(['channel' => null, 'message' => 'Fuera de horario', 'is_active' => true]);

        $conversation = $this->conversationWithSpanishCustomer(['channel' => 'web']);

        $this->listener()->handle(new ConversationCreated($conversation));

        $this->assertSame(0, ConversationItem::query()->where('conversation_id', $conversation->id)->count());
    }

    public function test_does_nothing_when_no_active_off_hours_response_configured(): void
    {
        $this->closeAllBusinessHours();

        $conversation = $this->conversationWithSpanishCustomer(['channel' => 'web']);

        $this->listener()->handle(new ConversationCreated($conversation));

        $this->assertSame(0, ConversationItem::query()->where('conversation_id', $conversation->id)->count());
    }
}
