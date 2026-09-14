<?php

namespace Modules\Media\Pulse\Recorders;

use Laravel\Pulse\Facades\Pulse;
use Modules\Media\Events\MediaFileUploaded;

class MediaUploadRecorder
{
    public string $listen = MediaFileUploaded::class;

    public function record(MediaFileUploaded $event): void
    {
        Pulse::record(
            type: 'media_upload',
            key: (string) $event->file->disk,
            value: $event->file->size,
        )->count();
    }
}
