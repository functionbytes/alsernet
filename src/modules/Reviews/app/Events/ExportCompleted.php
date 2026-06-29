<?php

namespace Modules\Reviews\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ExportCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $userId,
        public readonly string $filePath,
        public readonly int $rowCount
    ) {}
}
