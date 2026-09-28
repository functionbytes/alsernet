<?php

namespace Modules\Helpdesk\Tests\Feature;

use Modules\Helpdesk\Events\MessageReceived;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Tests\HelpdeskTestCase;

/**
 * Contrato del bot en el payload de broadcast (.message.received): un item
 * marcado como bot (metadata.sent_by_chatflow) debe viajar como saliente con
 * sender.type "Bot" / name "Asistente", y exponer options/prompt/cards para
 * que el widget pinte botones y tarjetas en vez de solo texto plano.
 *
 * Regresión: antes de este arreglo, PostsBotMessages no ponía user_id, así
 * que broadcastWith() clasificaba estos mensajes como entrantes del cliente
 * (isAgent = !is_null(user_id) = false).
 */
class MessageReceivedBotContractTest extends HelpdeskTestCase
{
    private function botItem(Conversation $conversation, array $metadata): ConversationItem
    {
        return ConversationItem::create([
            'conversation_id' => $conversation->id,
            'type' => 'message',
            'body' => '¿Confirmas el pedido?',
            'is_internal' => false,
            'metadata' => array_merge(['sent_by_chatflow' => true], $metadata),
        ]);
    }

    public function test_bot_message_broadcasts_as_outgoing_with_bot_sender_and_options(): void
    {
        $conversation = Conversation::factory()->create();
        $item = $this->botItem($conversation, [
            'bot_options' => ['Sí', 'No'],
            'bot_prompt' => '¿Confirmas el pedido?',
        ]);

        $payload = (new MessageReceived($conversation, $item))->broadcastWith();

        $this->assertSame('outgoing', $payload['message_type']);
        $this->assertSame('Bot', $payload['sender']['type']);
        $this->assertSame('Asistente', $payload['sender']['name']);
        $this->assertTrue($payload['is_bot']);
        $this->assertSame(['Sí', 'No'], $payload['options']);
        $this->assertSame('¿Confirmas el pedido?', $payload['prompt']);
        $this->assertTrue($payload['message']['is_from_agent']);
        $this->assertFalse($payload['message']['is_from_customer']);
    }

    public function test_bot_message_exposes_rich_message_card(): void
    {
        $conversation = Conversation::factory()->create();
        $item = $this->botItem($conversation, [
            'card' => ['title' => 'Camiseta azul', 'subtitle' => '19,99 €', 'image_url' => 'https://x/img.jpg'],
        ]);

        $payload = (new MessageReceived($conversation, $item))->broadcastWith();

        $this->assertSame([[
            'title' => 'Camiseta azul',
            'subtitle' => '19,99 €',
            'image_url' => 'https://x/img.jpg',
            'url' => null,
        ]], $payload['cards']);
    }

    public function test_customer_message_is_not_flagged_as_bot(): void
    {
        $conversation = Conversation::factory()->create();
        $item = ConversationItem::create([
            'conversation_id' => $conversation->id,
            'author_id' => $conversation->customer_id,
            'type' => 'message',
            'body' => 'Hola',
            'is_internal' => false,
        ]);

        $payload = (new MessageReceived($conversation, $item))->broadcastWith();

        $this->assertFalse($payload['is_bot']);
        $this->assertSame('incoming', $payload['message_type']);
        $this->assertSame([], $payload['options']);
        $this->assertNull($payload['prompt']);
    }
}
