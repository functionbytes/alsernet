<?php

namespace Modules\Helpdesk\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Helpdesk\Events\ConversationClosed;
use Modules\Helpdesk\Listeners\SendFarewellOnConversationClosed;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationFarewell;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTranslate\Services\CachedTranslator;
use Tests\TestCase;

class SendFarewellOnConversationClosedTest extends TestCase
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

        ConversationFarewell::query()->delete();
    }

    private function listener(): SendFarewellOnConversationClosed
    {
        return new SendFarewellOnConversationClosed;
    }

    private function conversationWithSpanishCustomer(array $overrides = []): Conversation
    {
        return Conversation::factory()
            ->for(Customer::factory()->state(['language' => 'es']), 'customer')
            ->create($overrides);
    }

    public function test_persists_farewell_visible_in_thread_on_close(): void
    {
        ConversationFarewell::query()->create(['channel' => null, 'message' => 'Gracias por contactarnos.', 'is_active' => true]);

        $conversation = $this->conversationWithSpanishCustomer(['channel' => 'web', 'external_sender_id' => null]);

        $this->listener()->handle(new ConversationClosed($conversation));

        $item = ConversationItem::query()
            ->where('conversation_id', $conversation->id)
            ->where('metadata->auto_reply', 'farewell')
            ->first();

        $this->assertNotNull($item);
        $this->assertSame('Gracias por contactarnos.', $item->body);
        $this->assertNull($item->user_id);
        $this->assertNull($item->author_id);
    }

    public function test_prefers_language_specific_response_and_skips_translation(): void
    {
        ConversationFarewell::query()->create(['channel' => null, 'language' => null, 'message' => 'Gracias.', 'is_active' => true]);
        ConversationFarewell::query()->create(['channel' => null, 'language' => 'en', 'message' => 'Thank you.', 'is_active' => true]);

        $this->mock(CachedTranslator::class);

        $conversation = Conversation::factory()
            ->for(Customer::factory()->state(['language' => 'en']), 'customer')
            ->create(['channel' => 'whatsapp']);

        $this->listener()->handle(new ConversationClosed($conversation));

        $item = ConversationItem::query()->where('conversation_id', $conversation->id)->first();

        $this->assertSame('Thank you.', $item->body);
    }

    public function test_does_nothing_when_no_active_farewell_configured(): void
    {
        $conversation = $this->conversationWithSpanishCustomer(['channel' => 'web']);

        $this->listener()->handle(new ConversationClosed($conversation));

        $this->assertSame(0, ConversationItem::query()->where('conversation_id', $conversation->id)->count());
    }
}
