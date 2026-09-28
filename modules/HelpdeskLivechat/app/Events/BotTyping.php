<?php

namespace Modules\HelpdeskLivechat\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Modules\Helpdesk\Concerns\BroadcastsToWidgetConversation;
use Modules\Helpdesk\Models\Conversation;

/**
 * "Escribiendo…" del asistente IA en el widget mientras genera la respuesta.
 * Mismo nombre de evento que el de los agentes (UserTyping) para reutilizar
 * el indicador; ttl más largo porque la IA con herramientas tarda más de los
 * 5 s del agente humano. Se emite al momento (sin cola): con cola el
 * indicador llegaría tarde o después de la respuesta.
 */
class BotTyping implements ShouldBroadcastNow
{
    use BroadcastsToWidgetConversation, Dispatchable, InteractsWithSockets;

    public function __construct(
        public Conversation $conversation,
        public bool $isTyping = true,
    ) {}

    public function broadcastOn(): array
    {
        return array_values(array_filter([$this->widgetConversationChannel($this->conversation)]));
    }

    public function broadcastWith(): array
    {
        return [
            'is_typing' => $this->isTyping,
            'is_bot' => true,
            'ttl' => 30,
        ];
    }

    public function broadcastAs(): string
    {
        return 'UserTyping';
    }
}
