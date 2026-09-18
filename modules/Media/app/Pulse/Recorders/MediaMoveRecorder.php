<?php

namespace Modules\Media\Pulse\Recorders;

use Laravel\Pulse\Facades\Pulse;
use Modules\Media\Events\MediaFileMoved;

class MediaMoveRecorder
{
    public string $listen = MediaFileMoved::class;

    public function record(MediaFileMoved $event): void
    {
        Pulse::record(
            type: 'media_move',
            key: sprintf('%s → %s', $event->previousFolderId ?? 'root', $event->newFolderId ?? 'root'),
            value: 1,
        )->count();
    }
}
