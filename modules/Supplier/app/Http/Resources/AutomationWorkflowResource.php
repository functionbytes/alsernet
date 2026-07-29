<?php

namespace Modules\Supplier\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AutomationWorkflowResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uid' => $this->uid,
            'name' => $this->name,
            'description' => $this->description,
            'workflowType' => $this->workflow_type,
            'externalWorkflowId' => $this->external_workflow_id,
            'webhookUrl' => $this->webhook_url,
            'isActive' => $this->is_active,
            'priority' => $this->priority,
            'timeoutSeconds' => $this->timeout_seconds,
            'maxRetries' => $this->max_retries,
            'stats' => [
                'totalExecutions' => $this->total_executions,
                'successfulExecutions' => $this->successful_executions,
                'failedExecutions' => $this->failed_executions,
                'successRate' => $this->success_rate,
            ],
            'lastExecutedAt' => $this->last_executed_at?->toIso8601String(),
            'createdAt' => $this->created_at->toIso8601String(),
            'updatedAt' => $this->updated_at->toIso8601String(),
        ];
    }
}
