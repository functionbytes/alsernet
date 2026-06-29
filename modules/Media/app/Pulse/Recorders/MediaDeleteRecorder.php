<?php

namespace Modules\Media\Pulse\Recorders;

use Laravel\Pulse\Facades\Pulse;
use Modules\Media\Events\MediaFileDeleted;

class MediaDeleteRecorder
{
    public string $listen = MediaFileDeleted::class;

    public function record(MediaFileDeleted $event): void
    {
        Pulse::record(
            type: 'media_delete',
            key: (string) $event->file->disk,
            value: $event->file->size,
        )->count();
    }
}
