<?php

namespace Modules\Helpdesk\Services\AI;

class AudioTranscriptionService
{
    public function __construct(
        private readonly AiClient $client
    ) {}

    public function transcribe(string $absolutePath): ?string
    {
        if (! $this->client->isEnabled()) {
            return null;
        }

        if (! file_exists($absolutePath)) {
            return null;
        }

        return $this->client->transcribe($absolutePath);
    }
}
