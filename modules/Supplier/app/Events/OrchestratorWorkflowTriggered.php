<?php

namespace Modules\Supplier\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when the orchestrator workflow is triggered
 */
class OrchestratorWorkflowTriggered
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance
     */
    public function __construct(
        public string $workflowId,
        public string $supplierId,
        public string $action,
        public array $payload = []
    ) {}
}
