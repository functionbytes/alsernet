<?php

namespace Modules\Supplier\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SyncBatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uid' => $this->uid,
            'batchName' => $this->batch_name,
            'syncType' => $this->sync_type,
            'status' => $this->status,
            'priority' => $this->priority,
            'supplierId' => $this->supplier_id,
            'triggeredBy' => $this->triggered_by,
            'stats' => [
                'totalItems' => $this->total_items,
                'processedItems' => $this->processed_items,
                'failedItems' => $this->failed_items,
                'totalBatches' => $this->total_batches,
                'processedBatches' => $this->processed_batches,
                'progressPercent' => $this->progress_percentage,
                'successRate' => $this->success_rate,
                'durationSeconds' => $this->duration_seconds,
            ],
            'retryAttempt' => $this->retry_attempt,
            'maxRetries' => $this->max_retries,
            'startedAt' => $this->started_at?->toIso8601String(),
            'completedAt' => $this->completed_at?->toIso8601String(),
            'logs' => $this->whenLoaded('logs'),
            'failures' => $this->whenLoaded('failures'),
            'createdAt' => $this->created_at->toIso8601String(),
            'updatedAt' => $this->updated_at->toIso8601String(),
        ];
    }
}
