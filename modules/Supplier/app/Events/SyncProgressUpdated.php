<?php

namespace Modules\Supplier\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event broadcast during supplier synchronization progress updates
 *
 * Fired from SyncStatusService::updateProgress() to provide real-time
 * progress information to subscribed Livewire components. Only authorized
 * users who have access to the sync status will receive this event.
 *
 * Channel: private-sync-{statusId}
 * Event Name: sync-progress-updated
 */
class SyncProgressUpdated implements ShouldBroadcast
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
        public int $percentComplete,
        public string $currentPhase,
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
        return 'sync-progress-updated';
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
            'percentComplete' => $this->percentComplete,
            'currentPhase' => $this->currentPhase,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
