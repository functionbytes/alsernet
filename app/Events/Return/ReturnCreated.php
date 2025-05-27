<?php

namespace App\Events\Return;

use App\Models\Return\ReturnRequest;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReturnCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $returnRequest;
    public $createdBy;
    public $metadata;

    public function __construct(ReturnRequest $returnRequest, string $createdBy = 'web', array $metadata = [])
    {
        $this->returnRequest = $returnRequest;
        $this->createdBy = $createdBy;
        $this->metadata = $metadata;
    }

    /**
     * Obtener información del evento para logs
     */
    public function getEventData(): array
    {
        return [
            'event' => 'return_created',
            'return_id' => $this->returnRequest->id_return_request,
            'order_id' => $this->returnRequest->id_order,
            'customer_email' => $this->returnRequest->email,
            'return_type' => $this->returnRequest->id_return_type,
            'logistics_mode' => $this->returnRequest->logistics_mode,
            'created_by' => $this->createdBy,
            'timestamp' => now()->toISOString(),
            'metadata' => $this->metadata
        ];
    }
}
