<?php

namespace Modules\Helpdesk\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Modules\Helpdesk\Events\ConversationCreated;
use Modules\Helpdesk\Listeners\SendGreetingOnConversationCreated;
use Modules\Helpdesk\Models\BusinessHour;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationGreeting;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTranslate\Services\CachedTranslator;
use Tests\TestCase;

/**
 * Mismos escenarios que RespondOffHoursOnConversationCreatedTest, pero con
 * horario ABIERTO en vez de cerrado (mutuamente excluyentes por diseño).
 */
class SendGreetingOnConversationCreatedTest extends TestCase
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
        ConversationGreeting::query()->delete();
    }

    private function openAllBusinessHours(): void
    {
        BusinessHour::query()->update(['is_open' => true, 'opens_at' => '00:00:00', 'closes_at' => '23:59:00']);
    }

    private function closeAllBusinessHours(): void
    {
        BusinessHour::query()->update(['is_open' => false]);
    }

    private function listener(): SendGreetingOnConversationCreated
    {
        return new SendGreetingOnConversationCreated;
    }

    private function conversationWithSpanishCustomer(array $overrides = []): Conversation
    {
        return Conversation::factory()
            ->for(Customer::factory()->state(['language' => 'es']), 'customer')
            ->create($overrides);
    }

    public function test_persists_greeting_visible_in_thread_when_open(): void
    {
        $this->openAllBusinessHours();

        ConversationGreeting::query()->create(['channel' => null, 'message' => '¡Hola! Bienvenido.', 'is_active' => true]);

        $conversation = $this->conversationWithSpanishCustomer(['channel' => 'web', 'external_sender_id' => null]);

        $this->listener()->handle(new ConversationCreated($conversation));

        $item = ConversationItem::query()
            ->where('conversation_id', $conversation->id)
            ->where('metadata->auto_reply', 'greeting')
            ->first();

        $this->assertNotNull($item);
        $this->assertSame('¡Hola! Bienvenido.', $item->body);
        $this->assertNull($item->user_id);
        $this->assertNull($item->author_id);
    }

    public function test_prefers_language_specific_response_and_skips_translation(): void
    {
        $this->openAllBusinessHours();

        ConversationGreeting::query()->create(['channel' => null, 'language' => null, 'message' => 'Hola.', 'is_active' => true]);
        ConversationGreeting::query()->create(['channel' => null, 'language' => 'en', 'message' => 'Hi there.', 'is_active' => true]);

        $this->mock(CachedTranslator::class);

        $conversation = Conversation::factory()
            ->for(Customer::factory()->state(['language' => 'en']), 'customer')
            ->create(['channel' => 'whatsapp']);

        $this->listener()->handle(new ConversationCreated($conversation));

        $item = ConversationItem::query()->where('conversation_id', $conversation->id)->first();

        $this->assertSame('Hi there.', $item->body);
    }

    public function test_does_nothing_when_business_is_closed(): void
    {
        $this->closeAllBusinessHours();

        ConversationGreeting::query()->create(['channel' => null, 'message' => 'Hola.', 'is_active' => true]);

        $conversation = $this->conversationWithSpanishCustomer(['channel' => 'web']);

        $this->listener()->handle(new ConversationCreated($conversation));

        $this->assertSame(0, ConversationItem::query()->where('conversation_id', $conversation->id)->count());
    }

    public function test_does_nothing_when_no_active_greeting_configured(): void
    {
        $this->openAllBusinessHours();

        $conversation = $this->conversationWithSpanishCustomer(['channel' => 'web']);

        $this->listener()->handle(new ConversationCreated($conversation));

        $this->assertSame(0, ConversationItem::query()->where('conversation_id', $conversation->id)->count());
    }
}
