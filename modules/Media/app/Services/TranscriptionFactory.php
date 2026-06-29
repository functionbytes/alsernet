<?php

namespace Modules\Media\Services;

use Modules\Media\Contracts\TranscriptionDriver;
use Modules\Media\Drivers\Transcription\NullDriver;
use Modules\Media\Drivers\Transcription\WhisperApiDriver;

class TranscriptionFactory
{
    public static function make(): TranscriptionDriver
    {
        $driver = config('media.ai.transcription.driver', 'null');
        $apiKey = (string) config('media.ai.transcription.api_key');

        return match ($driver) {
            'whisper' => new WhisperApiDriver($apiKey),
            default => new NullDriver,
        };
    }
}
