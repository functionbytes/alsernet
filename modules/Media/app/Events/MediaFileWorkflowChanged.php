<?php

namespace Modules\Media\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Media\Models\MediaFile;

class MediaFileWorkflowChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly MediaFile $file,
        public readonly string $fromStatus,
        public readonly string $toStatus,
        public readonly int $causerId
    ) {}
}
