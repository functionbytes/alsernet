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
use Modules\HelpdeskTranslate\Listeners\TranslateOutgoingMessage;
use Modules\HelpdeskTranslate\Services\CachedTranslator;
use Tests\TestCase;

/**
 * Covers the outgoing auto-translation listener.
 *
 * Regression target: the listener used to bail out unconditionally when the
 * customer's stored language was 'es', even if the agent was writing in
 * another locale — meaning Spanish-speaking customers chatting with an
 * English-speaking agent never saw a translation. The fix compares against
 * the agent locale instead of a hardcoded 'es'.
 */
class TranslateOutgoingMessageListenerTest extends TestCase
{
    use DatabaseTransactions;

    /** @var string[] */
    protected $connectionsToTransact = ['helpdesk'];

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();

        Setting::set('helpdesktranslate.auto_translate_outgoing', true, 'helpdesktranslate');
        Setting::set('helpdesktranslate.provider', 'libretranslate', 'helpdesktranslate');
        Setting::set('helpdesktranslate.libretranslate.endpoint', 'http://fake-libretranslate.test/translate', 'helpdesktranslate');
        Setting::set('helpdesktranslate.deepl.key', '', 'helpdesktranslate');
    }

    public function test_skips_when_auto_outgoing_disabled(): void
    {
        Setting::set('helpdesktranslate.auto_translate_outgoing', false, 'helpdesktranslate');

        [, $item] = $this->scenario(agentLocale: 'es', customerLang: 'en');

        Http::fake([]); // no stubs — any HTTP call would error

        $this->listener()->handle(new ConversationMessageCreated($item));

        $item->refresh();
        $this->assertNull($item->outgoing_translated_body);
    }

    public function test_skips_when_message_is_from_customer(): void
    {
        Setting::set('helpdesktranslate.default_target', 'es', 'helpdesktranslate');

        $status = $this->openStatus();
        $customer = Customer::factory()->create(['language' => 'en']);
        $conversation = Conversation::factory()->create(['status_id' => $status->id, 'customer_id' => $customer->id]);
        $item = ConversationItem::factory()->create([
            'conversation_id' => $conversation->id,
            'user_id' => null,
            'author_id' => $customer->id,
            'body' => 'Hello, I need help',
        ]);

        Http::fake([]);

        $this->listener()->handle(new ConversationMessageCreated($item));

        $item->refresh();
        $this->assertNull($item->outgoing_translated_body);
    }

    public function test_skips_internal_notes(): void
    {
        [, $item] = $this->scenario(agentLocale: 'es', customerLang: 'en', isInternal: true);

        Http::fake([]);

        $this->listener()->handle(new ConversationMessageCreated($item));

        $item->refresh();
        $this->assertNull($item->outgoing_translated_body);
    }

    public function test_skips_when_customer_language_matches_agent_locale(): void
    {
        // Agent writes in ES, customer reads in ES → no translation needed.
        [, $item] = $this->scenario(agentLocale: 'es', customerLang: 'es');

        Http::fake([]);

        $this->listener()->handle(new ConversationMessageCreated($item));

        $item->refresh();
        $this->assertNull($item->outgoing_translated_body);
    }

    /**
     * Regression for the hardcoded `=== 'es'` short-circuit. With agent
     * writing in EN and customer reading in ES the translation MUST run.
     */
    public function test_translates_when_agent_locale_is_not_spanish_and_customer_is(): void
    {
        [, $item] = $this->scenario(
            agentLocale: 'en',
            customerLang: 'es',
            body: 'Hello, how can I help you?',
        );

        Http::fake([
            'fake-libretranslate.test/translate' => Http::response([
                'translatedText' => 'Hola, ¿cómo puedo ayudarte?',
                'detectedLanguage' => ['language' => 'en', 'confidence' => 0.99],
            ], 200),
        ]);

        $this->listener()->handle(new ConversationMessageCreated($item));

        $item->refresh();
        $this->assertSame('Hola, ¿cómo puedo ayudarte?', $item->outgoing_translated_body);
        $this->assertSame('es', $item->outgoing_target_locale);
    }

    public function test_translates_when_agent_writes_in_es_and_customer_reads_in_en(): void
    {
        [, $item] = $this->scenario(
            agentLocale: 'es',
            customerLang: 'en',
            body: 'Hola, ¿en qué puedo ayudarte?',
        );

        Http::fake([
            'fake-libretranslate.test/translate' => Http::response([
                'translatedText' => 'Hello, how can I help you?',
                'detectedLanguage' => ['language' => 'es', 'confidence' => 0.99],
            ], 200),
        ]);

        $this->listener()->handle(new ConversationMessageCreated($item));

        $item->refresh();
        $this->assertSame('Hello, how can I help you?', $item->outgoing_translated_body);
        $this->assertSame('en', $item->outgoing_target_locale);
    }

    /**
     * @return array{0: Conversation, 1: ConversationItem}
     */
    private function scenario(
        string $agentLocale,
        ?string $customerLang,
        string $body = 'Outgoing agent message that is long enough to translate.',
        bool $isInternal = false,
    ): array {
        Setting::set('helpdesktranslate.default_target', $agentLocale, 'helpdesktranslate');

        $status = $this->openStatus();
        $customer = Customer::factory()->create(['language' => $customerLang]);
        $conversation = Conversation::factory()->create([
            'status_id' => $status->id,
            'customer_id' => $customer->id,
        ]);

        $item = ConversationItem::factory()->create([
            'conversation_id' => $conversation->id,
            'user_id' => 1, // outgoing agent message
            'author_id' => null,
            'body' => $body,
            'is_internal' => $isInternal,
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

    private function listener(): TranslateOutgoingMessage
    {
        return new TranslateOutgoingMessage(app(CachedTranslator::class));
    }
}
