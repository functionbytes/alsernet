<?php

namespace Modules\HelpdeskTranslate\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Modules\Helpdesk\Events\ConversationMessageCreated;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Setting;
use Modules\HelpdeskTranslate\Listeners\TranslateIncomingMessage;
use Modules\HelpdeskTranslate\Services\CachedTranslator;
use Tests\TestCase;

/**
 * Covers the incoming auto-translation listener.
 *
 * The listener must:
 *  - Bail out cleanly when disabled, when the message is from an agent, or
 *    when the customer language matches the agent locale.
 *  - Persist the translation + source_locale on the conversation item when
 *    the customer language differs from the agent locale.
 */
class TranslateIncomingMessageListenerTest extends TestCase
{
    use DatabaseTransactions;

    /** @var string[] */
    protected $connectionsToTransact = ['helpdesk'];

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();

        Setting::set('helpdesktranslate.auto_translate_incoming', true, 'helpdesktranslate');
        Setting::set('helpdesktranslate.provider', 'libretranslate', 'helpdesktranslate');
        Setting::set('helpdesktranslate.libretranslate.endpoint', 'http://fake-libretranslate.test/translate', 'helpdesktranslate');
        Setting::set('helpdesktranslate.deepl.key', '', 'helpdesktranslate');
        Setting::set('helpdesktranslate.default_target', 'es', 'helpdesktranslate');
    }

    public function test_skips_when_auto_incoming_disabled(): void
    {
        Setting::set('helpdesktranslate.auto_translate_incoming', false, 'helpdesktranslate');

        [, $item] = $this->incomingScenario(customerLang: 'en');

        Http::fake([]);

        $this->listener()->handle(new ConversationMessageCreated($item));

        $item->refresh();
        $this->assertNull($item->translated_body);
    }

    public function test_skips_agent_messages(): void
    {
        $status = $this->openStatus();
        $customer = Customer::factory()->create(['language' => 'en']);
        $conversation = Conversation::factory()->create([
            'status_id' => $status->id,
            'customer_id' => $customer->id,
        ]);
        $item = ConversationItem::factory()->create([
            'conversation_id' => $conversation->id,
            'user_id' => 42, // agent
            'author_id' => null,
            'body' => 'Agent reply',
        ]);

        Http::fake([]);

        $this->listener()->handle(new ConversationMessageCreated($item));

        $item->refresh();
        $this->assertNull($item->translated_body);
    }

    public function test_skips_when_customer_language_matches_agent_locale(): void
    {
        // Customer writes in ES, agent reads in ES → nothing to translate.
        [, $item] = $this->incomingScenario(customerLang: 'es');

        Http::fake([]);

        $this->listener()->handle(new ConversationMessageCreated($item));

        $item->refresh();
        $this->assertNull($item->translated_body);
    }

    public function test_translates_when_customer_speaks_a_different_language(): void
    {
        [, $item] = $this->incomingScenario(
            customerLang: 'en',
            body: 'Hello, I need help with my order.',
        );

        Http::fake([
            'fake-libretranslate.test/translate' => Http::response([
                'translatedText' => 'Hola, necesito ayuda con mi pedido.',
                'detectedLanguage' => ['language' => 'en', 'confidence' => 0.99],
            ], 200),
        ]);

        $this->listener()->handle(new ConversationMessageCreated($item));

        $item->refresh();
        $this->assertSame('Hola, necesito ayuda con mi pedido.', $item->translated_body);
        $this->assertSame('en', $item->source_locale);
    }

    /**
     * ConversationMessageCreated también se emite para eventos de actividad
     * ("La conversación fue silenciada"): con user_id nulo se confundían con un
     * mensaje del cliente y gastaban cupo del proveedor.
     */
    public function test_skips_system_activity_items_that_have_no_user(): void
    {
        [, $item] = $this->incomingScenario(customerLang: 'en', body: 'La conversación fue silenciada');
        $item->forceFill(['type' => 'activity', 'item_type' => 'activity'])->saveQuietly();

        Http::fake([]);

        $this->listener()->handle(new ConversationMessageCreated($item->fresh()));

        Http::assertNothingSent();
        $this->assertNull($item->fresh()->translated_body);
    }

    public function test_skips_internal_items_that_have_no_user(): void
    {
        [, $item] = $this->incomingScenario(customerLang: 'en', body: 'Resumen automático de la conversación.');
        $item->forceFill(['is_internal' => true])->saveQuietly();

        Http::fake([]);

        $this->listener()->handle(new ConversationMessageCreated($item->fresh()));

        Http::assertNothingSent();
        $this->assertNull($item->fresh()->translated_body);
    }

    /**
     * @return array{0: Conversation, 1: ConversationItem}
     */
    private function incomingScenario(
        ?string $customerLang,
        string $body = 'Customer inbound message',
    ): array {
        $status = $this->openStatus();
        $customer = Customer::factory()->create(['language' => $customerLang]);
        $conversation = Conversation::factory()->create([
            'status_id' => $status->id,
            'customer_id' => $customer->id,
        ]);
        $item = ConversationItem::factory()->create([
            'conversation_id' => $conversation->id,
            'user_id' => null,
            'author_id' => $customer->id,
            'body' => $body,
        ]);

        return [$conversation, $item];
    }

    private function openStatus(): ConversationStatus
    {
        return ConversationStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1],
        );
    }

    private function listener(): TranslateIncomingMessage
    {
        return new TranslateIncomingMessage(app(CachedTranslator::class));
    }
}
