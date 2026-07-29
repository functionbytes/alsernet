<?php

namespace Modules\Supplier\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event broadcast when supplier synchronization fails
 *
 * Fired from SyncStatusService::failSync() to notify subscribed Livewire components
 * that a sync operation has encountered an error and failed. Includes error details
 * and retry suggestions to help users recover from the failure.
 *
 * Channel: private-sync-{statusId}
 * Event Name: sync-failed
 */
class SyncFailed implements ShouldBroadcast
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
        public string $errorMessage,
        public string $errorCode,
        public string $failureReason,
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
        return 'sync-failed';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'statusId' => $this->statusId,
            'batchId' => $this->batchId,
            'syncType' => $this->syncType,
            'totalItems' => $this->totalItems,
            'processedItems' => $this->processedItems,
            'failedItems' => $this->failedItems,
            'errorMessage' => $this->errorMessage,
            'errorCode' => $this->errorCode,
            'failureReason' => $this->failureReason,
            'retryMessage' => 'Puedes reintentar esta sincronización desde la sección de estado.',
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
