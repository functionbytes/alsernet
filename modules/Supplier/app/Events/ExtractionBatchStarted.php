<?php

namespace Modules\Supplier\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when an extraction batch job starts processing
 */
class ExtractionBatchStarted
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance
     */
    public function __construct(
        public string $batchId,
        public string $supplierId,
        public int $totalItems,
        public array $metadata = []
    ) {}
}
