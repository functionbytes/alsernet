<?php

namespace Modules\Media\Drivers\Transcription;

use Illuminate\Support\Facades\Http;
use Modules\Media\Contracts\TranscriptionDriver;

class WhisperApiDriver implements TranscriptionDriver
{
    public function __construct(private readonly string $apiKey) {}

    public function transcribe(string $audioPath): ?string
    {
        $response = Http::timeout(120)
            ->withToken($this->apiKey)
            ->attach('file', file_get_contents($audioPath), basename($audioPath))
            ->post('https://api.openai.com/v1/audio/transcriptions', [
                'model' => 'whisper-1',
                'response_format' => 'text',
            ]);

        if (! $response->successful()) {
            return null;
        }

        $transcript = trim($response->body());

        return $transcript !== '' ? $transcript : null;
    }
}
