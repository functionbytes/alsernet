<?php

namespace Modules\Helpdesk\Tests\Unit;

use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Notifications\MessageReceivedNotification;
use Modules\Helpdesk\Tests\HelpdeskTestCase;

/**
 * El aviso de mensaje entrante dice DE QUIÉN es ("Ana López · #12493") en
 * vez de solo el id de conversación, y no arrastra HTML del mensaje.
 */
class MessageReceivedNotificationTextTest extends HelpdeskTestCase
{
    private function notification(?string $customerName, string $body): MessageReceivedNotification
    {
        $conversation = new Conversation;
        $conversation->id = 12493;
        $conversation->setRelation('customer', $customerName === null ? null : new Customer(['name' => $customerName]));

        $message = new ConversationItem(['body' => $body]);

        return new MessageReceivedNotification($conversation, $message);
    }

    public function test_message_includes_customer_name_and_conversation_id(): void
    {
        $data = $this->notification('Ana López', 'Hola, ¿dónde está mi pedido?')->toArray(new \stdClass);

        $this->assertSame('Ana López · #12493: Hola, ¿dónde está mi pedido?', $data['message']);
        $this->assertSame(12493, $data['entity_id']);
    }

    public function test_falls_back_to_conversation_label_without_customer_name(): void
    {
        $data = $this->notification(null, 'Hola')->toArray(new \stdClass);

        $this->assertSame('Conversación #12493: Hola', $data['message']);
    }

    public function test_html_is_stripped_from_preview_and_web_push_uses_same_label(): void
    {
        $n = $this->notification('Ana López', '<b>Hola</b><script>x()</script>');

        $this->assertStringNotContainsString('<', $n->toArray(new \stdClass)['message']);
        $this->assertStringStartsWith('Ana López · #12493: Hola', $n->toWebPush(new \stdClass)['body']);
    }
}
