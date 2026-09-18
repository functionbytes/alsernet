<?php

namespace Modules\Supplier\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AutomationExecutionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uid' => $this->uid,
            'workflowId' => $this->workflow_id,
            'supplierId' => $this->supplier_id,
            'sourceId' => $this->source_id,
            'status' => $this->status,
            'triggerType' => $this->trigger_type,
            'externalExecutionId' => $this->external_execution_id,
            'items' => [
                'processed' => $this->items_processed,
                'succeeded' => $this->items_succeeded,
                'failed' => $this->items_failed,
            ],
            'durationMs' => $this->duration_ms,
            'retryCount' => $this->retry_count,
            'errorDetails' => $this->error_details,
            'startedAt' => $this->started_at?->toIso8601String(),
            'completedAt' => $this->completed_at?->toIso8601String(),
            'createdAt' => $this->created_at->toIso8601String(),
        ];
    }
}
