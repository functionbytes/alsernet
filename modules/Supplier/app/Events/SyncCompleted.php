<?php

namespace Modules\Supplier\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event broadcast when supplier synchronization completes successfully
 *
 * Fired from SyncStatusService::completeSync() to notify subscribed Livewire
 * components that a sync operation has finished with success. Includes completion
 * details such as duration and final item counts.
 *
 * Channel: private-sync-{statusId}
 * Event Name: sync-completed
 */
class SyncCompleted implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance
     */
    public function __construct(
        public string $statusId,
        public string $batchId,
        public string $syncType,
        public int $totalItems,
        public int $processedItems,
        public int $failedItems,
        public int $duration,
        public string $completedAt,
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('sync-'.$this->statusId),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'sync-completed';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $successRate = $this->totalItems > 0 ? round(($this->processedItems / $this->totalItems) * 100, 2) : 0;

        return [
            'statusId' => $this->statusId,
            'batchId' => $this->batchId,
            'syncType' => $this->syncType,
            'totalItems' => $this->totalItems,
            'processedItems' => $this->processedItems,
            'failedItems' => $this->failedItems,
            'successRate' => $successRate,
            'duration' => $this->duration,
            'completedAt' => $this->completedAt,
            'message' => sprintf(
                'Sincronización completada: %d de %d elementos procesados exitosamente',
                $this->processedItems,
                $this->totalItems
            ),
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
