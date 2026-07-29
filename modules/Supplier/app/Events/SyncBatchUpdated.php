<?php

namespace Modules\Supplier\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event broadcast when individual batch status changes during synchronization
 *
 * Fired when an individual agent/worker completes processing items in a batch.
 * This event provides granular updates about batch-level progress, allowing
 * UI components to display per-batch status information.
 *
 * Channel: private-sync-batch-{batchId}
 * Event Name: sync-batch-updated
 */
class SyncBatchUpdated implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance
     */
    public function __construct(
        public string $batchId,
        public string $status,
        public string $syncType,
        public int $itemsProcessed,
        public int $itemsFailed,
        public int $percentComplete,
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('sync-batch-'.$this->batchId),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'sync-batch-updated';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'batchId' => $this->batchId,
            'status' => $this->status,
            'syncType' => $this->syncType,
            'itemsProcessed' => $this->itemsProcessed,
            'itemsFailed' => $this->itemsFailed,
            'percentComplete' => $this->percentComplete,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
