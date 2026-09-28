<?php

namespace Modules\HelpdeskLivechat\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Inbox;
use Modules\HelpdeskLivechat\Database\Factories\WebFactory;
use Modules\HelpdeskLivechat\Tests\Concerns\SeedsOpenConversationStatus;
use Tests\TestCase;

/**
 * Contrato del bot hacia el widget (GET messages): un item marcado como bot
 * (ChatFlow: metadata.sent_by_chatflow, o carrusel de producto autogenerado:
 * metadata.is_bot) debe viajar como saliente ('is_bot' + sender.type "Bot"),
 * y exponer los extras que el front pinta como UI interactiva — options/
 * prompt de quick_replies/csat, cards de rich_message — en vez de solo el
 * texto plano con la lista numerada embebida en el body.
 *
 * Antes (bug backend-chatflow #1) estos mensajes tenían user_id/author_id a
 * null y el widget los mostraba como si los hubiera escrito el visitante.
 */
class WidgetBotMessageContractTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsOpenConversationStatus;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    private function conversation(): Conversation
    {
        $this->seedOpenConversationStatus();
        $web = WebFactory::new()->create();
        $customer = Customer::factory()->create(['email' => 'bot-contract@example.com']);

        $inbox = Inbox::firstOrCreate(
            ['channel_type' => 'web', 'channel_id' => $web->id],
            ['uid' => (string) Str::uuid(), 'name' => 'Widget Test', 'is_active' => true]
        );

        return Conversation::factory()->create([
            'customer_id' => $customer->id,
            'inbox_id' => $inbox->id,
            'channel' => 'web',
            'metadata' => ['widget_pubsub_token' => 'pubsub_bot_contract_token'],
        ]);
    }

    public function test_bot_message_with_options_is_outgoing_with_prompt_and_options(): void
    {
        $conversation = $this->conversation();

        ConversationItem::create([
            'conversation_id' => $conversation->id,
            'type' => 'message',
            'body' => "¿Confirmas el pedido?\n1. Sí\n2. No",
            'is_internal' => false,
            'metadata' => [
                'sent_by_chatflow' => true,
                'bot_options' => ['Sí', 'No'],
                'bot_prompt' => '¿Confirmas el pedido?',
            ],
        ]);

        $response = $this->withHeaders(['X-Conversation-Token' => 'pubsub_bot_contract_token'])->getJson(
            route('helpdesk-livechat.widget.conversation.messages.index', $conversation->id)
                .'?customer_email='.urlencode($conversation->customer->email)
        )->assertOk();

        $message = $response->json('data.messages.0');

        $this->assertSame('outgoing', $message['message_type']);
        $this->assertTrue($message['is_bot']);
        $this->assertSame('Bot', $message['sender']['type']);
        $this->assertSame(['Sí', 'No'], $message['options']);
        $this->assertSame('¿Confirmas el pedido?', $message['prompt']);
        $this->assertSame([], $message['cards']);
    }

    public function test_bot_message_with_single_card_exposes_it_in_cards(): void
    {
        $conversation = $this->conversation();

        ConversationItem::create([
            'conversation_id' => $conversation->id,
            'type' => 'message',
            'body' => 'Camiseta azul',
            'is_internal' => false,
            'metadata' => [
                'sent_by_chatflow' => true,
                'card' => ['title' => 'Camiseta azul', 'subtitle' => '19,99 €', 'image_url' => 'https://x/img.jpg'],
            ],
        ]);

        $response = $this->withHeaders(['X-Conversation-Token' => 'pubsub_bot_contract_token'])->getJson(
            route('helpdesk-livechat.widget.conversation.messages.index', $conversation->id)
                .'?customer_email='.urlencode($conversation->customer->email)
        )->assertOk();

        $message = $response->json('data.messages.0');

        $this->assertTrue($message['is_bot']);
        $this->assertSame([[
            'title' => 'Camiseta azul',
            'subtitle' => '19,99 €',
            'image_url' => 'https://x/img.jpg',
            'url' => null,
        ]], $message['cards']);
    }

    public function test_authorless_auto_reply_is_outgoing_not_the_customer(): void
    {
        $conversation = $this->conversation();

        ConversationItem::create([
            'conversation_id' => $conversation->id,
            'type' => 'message',
            'body' => 'Gracias por escribirnos, en breve un agente te atenderá.',
            'is_internal' => false,
            'metadata' => ['auto_reply' => 'workflow'],
        ]);

        $message = $this->withHeaders(['X-Conversation-Token' => 'pubsub_bot_contract_token'])->getJson(
            route('helpdesk-livechat.widget.conversation.messages.index', $conversation->id)
                .'?customer_email='.urlencode($conversation->customer->email)
        )->assertOk()->json('data.messages.0');

        $this->assertSame('outgoing', $message['message_type']);
        $this->assertTrue($message['is_bot']);
    }

    public function test_customer_message_is_not_flagged_as_bot(): void
    {
        $conversation = $this->conversation();

        ConversationItem::create([
            'conversation_id' => $conversation->id,
            'author_id' => $conversation->customer_id,
            'type' => 'message',
            'body' => 'Hola',
            'is_internal' => false,
        ]);

        $response = $this->withHeaders(['X-Conversation-Token' => 'pubsub_bot_contract_token'])->getJson(
            route('helpdesk-livechat.widget.conversation.messages.index', $conversation->id)
                .'?customer_email='.urlencode($conversation->customer->email)
        )->assertOk();

        $message = $response->json('data.messages.0');

        $this->assertFalse($message['is_bot']);
        $this->assertSame('incoming', $message['message_type']);
        $this->assertSame([], $message['options']);
        $this->assertNull($message['prompt']);
    }

    public function test_product_carousel_from_bot_is_flagged_and_outgoing(): void
    {
        $conversation = $this->conversation();

        ConversationItem::create([
            'conversation_id' => $conversation->id,
            'type' => 'product_carousel',
            'body' => '',
            'is_internal' => false,
            'metadata' => [
                'is_bot' => true,
                'products' => [['id' => 1, 'title' => 'Camiseta']],
            ],
        ]);

        $response = $this->withHeaders(['X-Conversation-Token' => 'pubsub_bot_contract_token'])->getJson(
            route('helpdesk-livechat.widget.conversation.messages.index', $conversation->id)
                .'?customer_email='.urlencode($conversation->customer->email)
        )->assertOk();

        $message = $response->json('data.messages.0');

        $this->assertTrue($message['is_bot']);
        $this->assertSame('outgoing', $message['message_type']);
        $this->assertSame('Bot', $message['sender']['type']);
        $this->assertSame('Asistente', $message['sender']['name']);
    }
}
