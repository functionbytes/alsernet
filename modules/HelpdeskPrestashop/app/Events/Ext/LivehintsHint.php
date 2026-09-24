<?php

namespace Modules\HelpdeskPrestashop\Events\Ext;

use App\Events\Concerns\BroadcastsOnServedQueue;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Extensión "livehints": aviso en vivo sobre el composer de la conversación
 * abierta del cliente cuando PrestaShop avisa de algo que le afecta (vuelta
 * a stock o bajada de precio de un producto que tiene en su lista de deseos
 * o con aviso de reposición, carrito abandonado, pedido nuevo, cambio de
 * estado de un pedido, solicitud de devolución).
 *
 * Mismo canal que PsCartLiveUpdate; el nombre del evento lleva el tipo
 * (ps.hint.<tipo>) para que el front escuche solo los que sabe pintar.
 * `data` son datos crudos (números, ids, nombres): el texto y el formato del
 * dinero los pone el front, escapados.
 */
class LivehintsHint implements ShouldBroadcastNow
{
    use BroadcastsOnServedQueue, Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public int $conversationId,
        public string $type,
        public array $data = [],
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('helpdesk.conversation.'.$this->conversationId),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'type' => $this->type,
            'data' => $this->data,
            'at' => now()->toIso8601String(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'ps.hint.'.$this->type;
    }
}
