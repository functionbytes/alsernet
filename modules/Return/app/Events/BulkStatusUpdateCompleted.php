<?php

namespace Modules\Returns\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when bulk status update completes successfully
 */
class BulkStatusUpdateCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public array $returnIds,
        public string $newStatus,
        public int $successCount,
        public int $failureCount,
        public string $updatedBy = 'system',
        public ?string $ipAddress = null,
        public ?string $userAgent = null
    ) {
        $this->ipAddress = $this->ipAddress ?? request()->ip();
        $this->userAgent = $this->userAgent ?? request()->userAgent();
    }

    /**
     * Obtener datos del evento para logs
     */
    public function getEventData(): array
    {
        return [
            'event' => 'bulk_status_update_completed',
            'return_ids' => $this->returnIds,
            'new_status' => $this->newStatus,
            'success_count' => $this->successCount,
            'failure_count' => $this->failureCount,
            'total_count' => count($this->returnIds),
            'updated_by' => $this->updatedBy,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'timestamp' => now()->toISOString(),
        ];
    }
}
