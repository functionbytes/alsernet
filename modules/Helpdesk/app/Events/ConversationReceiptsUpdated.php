<?php

namespace Modules\Helpdesk\Events;

use App\Events\Concerns\BroadcastsOnServedQueue;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * PERF-05: reemplaza el broadcast `item.created` disparado por cada ítem
 * marcado como entregado/leído. Un solo evento agregado con los ids
 * afectados evita N broadcasts (cada uno con el payload completo del
 * mensaje) por cada recibo de lectura/entrega de Messenger/Instagram.
 */
class ConversationReceiptsUpdated implements ShouldBroadcastNow
{
    use BroadcastsOnServedQueue, Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  array<int, int>  $itemIds
     */
    public function __construct(
        public int $conversationId,
        public string $field,
        public array $itemIds,
        public ?int $watermark = null,
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
            'field' => $this->field,
            'item_ids' => $this->itemIds,
            'watermark' => $this->watermark,
            'at' => now()->toIso8601String(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'receipts_updated';
    }
}
