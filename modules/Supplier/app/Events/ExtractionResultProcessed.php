<?php

namespace Modules\Supplier\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when an individual extraction result is processed
 */
class ExtractionResultProcessed
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance
     */
    public function __construct(
        public string $batchId,
        public string $supplierId,
        public string $resultId,
        public string $status,
        public array $result = []
    ) {}
}
