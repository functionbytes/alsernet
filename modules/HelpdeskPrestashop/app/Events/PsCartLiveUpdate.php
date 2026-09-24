<?php

namespace Modules\HelpdeskPrestashop\Events;

use App\Events\Concerns\BroadcastsOnServedQueue;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Avisa al panel del inbox, en vivo, de que el carrito de PrestaShop del
 * cliente de esta conversación acaba de cambiar (el cliente añadió/quitó un
 * producto, o cambió de dirección) — para que la pestaña "Tienda" se
 * refresque sola sin que el agente tenga que reabrir la conversación.
 */
class PsCartLiveUpdate implements ShouldBroadcastNow
{
    use BroadcastsOnServedQueue, Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $conversationId,
        public ?int $cartId,
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
            'cart_id' => $this->cartId,
            'at' => now()->toIso8601String(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'ps.cart.updated';
    }
}
