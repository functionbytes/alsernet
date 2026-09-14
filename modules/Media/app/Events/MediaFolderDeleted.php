<?php

namespace Modules\Media\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Media\Models\MediaFolder;

class MediaFolderDeleted
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly MediaFolder $folder) {}
}
