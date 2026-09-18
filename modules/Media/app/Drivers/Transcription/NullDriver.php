<?php

namespace Modules\Media\Drivers\Transcription;

use Modules\Media\Contracts\TranscriptionDriver;

class NullDriver implements TranscriptionDriver
{
    public function transcribe(string $audioPath): ?string
    {
        return null;
    }
}
