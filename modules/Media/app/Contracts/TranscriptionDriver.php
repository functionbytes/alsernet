<?php

namespace Modules\Media\Contracts;

interface TranscriptionDriver
{
    /**
     * Transcribe audio to text.
     */
    public function transcribe(string $audioPath): ?string;
}
