<?php

namespace Modules\Returns\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Event fired when bulk status update fails
 */
class BulkStatusUpdateFailed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public array $returnIds,
        public string $newStatus,
        public Throwable $exception,
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
            'event' => 'bulk_status_update_failed',
            'return_ids' => $this->returnIds,
            'new_status' => $this->newStatus,
            'error' => $this->exception->getMessage(),
            'error_code' => $this->exception->getCode(),
            'updated_by' => $this->updatedBy,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'timestamp' => now()->toISOString(),
        ];
    }
}
