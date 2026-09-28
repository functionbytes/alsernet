<?php

namespace Modules\HelpdeskLivechat\Events;

use App\Events\Concerns\BroadcastsOnServedQueue;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Modules\Helpdesk\Concerns\BroadcastsToWidgetConversation;
use Modules\Helpdesk\Models\Conversation;

/**
 * La cesta del visitante cambió desde el servidor (agente o bot añadieron un
 * producto): el widget relee la cesta y refresca el minicarrito de la tienda.
 * Sin datos de la cesta en el mensaje: el widget la lee de la propia tienda.
 */
class WidgetCartChanged implements ShouldBroadcastNow
{
    use BroadcastsOnServedQueue, BroadcastsToWidgetConversation, Dispatchable, InteractsWithSockets;

    public function __construct(
        public readonly Conversation $conversation,
        public readonly int $productId,
    ) {}

    public function broadcastOn(): array
    {
        $channel = $this->widgetConversationChannel($this->conversation);

        return $channel ? [$channel] : [];
    }

    public function broadcastAs(): string
    {
        return 'cart.changed';
    }

    public function broadcastWith(): array
    {
        return ['product_id' => $this->productId];
    }
}
