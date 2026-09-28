<?php

namespace Modules\HelpdeskChatFlow\Tests\Feature;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\HelpdeskChatFlow\Observers\ConversationItemObserver;
use Modules\HelpdeskChatFlow\Services\ChatFlowEngine;
use Modules\HelpdeskChatFlow\Tests\TestCase;

/**
 * Regresión: el saludo automático (auto_reply, sin user_id ni author_id) se
 * procesaba como la respuesta del cliente y el agente IA se contestaba a sí
 * mismo ("parece que hay un malentendido…").
 */
class ConversationItemObserverAutoReplyTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_authorless_auto_reply_never_reaches_the_engine(): void
    {
        $engine = Mockery::mock(ChatFlowEngine::class);
        $engine->shouldNotReceive('getActiveSession');
        $engine->shouldNotReceive('processMessage');

        $item = new ConversationItem;
        $item->setRawAttributes([
            'id' => 2,
            'conversation_id' => 10,
            'type' => 'message',
            'is_internal' => false,
            'user_id' => null,
            'author_id' => null,
            'body' => '¡Hola! Gracias por contactarnos, en breve te atenderá un agente.',
            'metadata' => json_encode(['auto_reply' => 'greeting']),
        ]);

        (new ConversationItemObserver($engine))->created($item);
    }
}
