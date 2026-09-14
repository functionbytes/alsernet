<?php

namespace Modules\Supplier\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when an extraction batch job completes successfully
 */
class ExtractionBatchCompleted
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance
     */
    public function __construct(
        public string $batchId,
        public string $supplierId,
        public int $processedItems,
        public int $failedItems,
        public array $metadata = []
    ) {}
}
