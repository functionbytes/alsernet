<?php

namespace Modules\Supplier\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Supplier\Models\Ai\AiContent;

class AiContentGenerated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public AiContent $content, public ?string $batchId = null) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('supplier-ai-content'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'content.generated';
    }

    public function broadcastWith(): array
    {
        return [
            'uid' => $this->content->uid,
            'status' => $this->content->status,
            'supplier_id' => $this->content->supplier_id,
            'product_id' => $this->content->supplier_product_id,
            'batch_id' => $this->batchId,
            'created_at' => $this->content->created_at?->toIso8601String(),
        ];
    }
}
