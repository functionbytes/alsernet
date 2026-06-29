<?php

namespace Modules\HelpdeskAgents\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EmbeddingService
{
    private string $apiKey;

    private string $model;

    private int $timeout;

    public function __construct()
    {
        $this->apiKey = config('helpdeskagents.embeddings.api_key')
            ?? config('services.openai.key', '');

        $this->model = config('helpdeskagents.embeddings.model', 'text-embedding-3-small');
        $this->timeout = config('helpdeskagents.embeddings.timeout', 30);
    }

    public function embed(string $text): array
    {
        $this->assertConfigured();

        $response = Http::withToken($this->apiKey)
            ->timeout($this->timeout)
            ->post('https://api.openai.com/v1/embeddings', [
                'model' => $this->model,
                'input' => $text,
                'encoding_format' => 'float',
            ]);

        if (! $response->successful()) {
            Log::error('EmbeddingService: API call failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException('Embedding API call failed: '.$response->status());
        }

        return $response->json('data.0.embedding');
    }

    public function embedBatch(array $texts): array
    {
        $this->assertConfigured();

        $response = Http::withToken($this->apiKey)
            ->timeout($this->timeout)
            ->post('https://api.openai.com/v1/embeddings', [
                'model' => $this->model,
                'input' => $texts,
                'encoding_format' => 'float',
            ]);

        if (! $response->successful()) {
            Log::error('EmbeddingService: batch API call failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException('Embedding batch API call failed: '.$response->status());
        }

        return array_map(
            fn (array $item): array => $item['embedding'],
            $response->json('data')
        );
    }

    public function isConfigured(): bool
    {
        return filled(config('helpdeskagents.embeddings.api_key'))
            || filled(config('services.openai.key'));
    }

    private function assertConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw new \RuntimeException('EmbeddingService: no API key configured.');
        }
    }
}
