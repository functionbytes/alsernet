<?php

namespace Modules\Helpdesk\Tests\Feature\Inbox;

use Modules\Helpdesk\Models\ConversationItem;

class ReplyToIdIdorTest extends InboxTestCase
{
    public function test_no_puede_citar_un_item_de_otra_conversacion(): void
    {
        $convA = $this->createConversation(['channel' => 'web']);
        $convB = $this->createConversation(['channel' => 'web']);
        $itemB = ConversationItem::factory()->create([
            'conversation_id' => $convB->id,
            'body' => 'secreto interno de la conversación B',
        ]);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.messages.store', $convA), [
                'body' => 'intento de fuga',
                'reply_to_id' => $itemB->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('reply_to_id');
    }

    public function test_si_puede_citar_un_item_de_la_misma_conversacion(): void
    {
        $conv = $this->createConversation(['channel' => 'web']);
        $item = ConversationItem::factory()->create([
            'conversation_id' => $conv->id,
            'body' => 'mensaje válido a citar',
        ]);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.messages.store', $conv), [
                'body' => 'respuesta citando',
                'reply_to_id' => $item->id,
            ])
            ->assertSuccessful();
    }
}
