<?php

namespace Modules\Media\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Media\Models\MediaFile;

class MediaFileMoved
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly MediaFile $file,
        public readonly ?int $previousFolderId,
        public readonly ?int $newFolderId,
    ) {}
}
