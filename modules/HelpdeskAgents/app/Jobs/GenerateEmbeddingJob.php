<?php

namespace Modules\HelpdeskAgents\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskAgents\Models\AiAgentKnowledgeBase;
use Modules\HelpdeskAgents\Services\EmbeddingService;

class GenerateEmbeddingJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public int $backoff = 30;

    public function __construct(
        private readonly AiAgentKnowledgeBase $knowledge
    ) {
        $this->onQueue('helpdesk-embeddings');
    }

    public function handle(EmbeddingService $embeddingService): void
    {
        if (! $embeddingService->isConfigured()) {
            Log::warning('GenerateEmbeddingJob: embedding service not configured, skipping', [
                'knowledge_id' => $this->knowledge->id,
            ]);

            return;
        }

        $embedding = $embeddingService->embed($this->knowledge->content);
        $norm = sqrt(array_sum(array_map(fn ($v) => $v ** 2, $embedding)));

        $this->knowledge->update([
            'embedding' => $embedding, // array cast handles JSON encoding
            'embedding_model' => config('helpdeskagents.embeddings.model', 'text-embedding-3-small'),
            'vector_norm' => $norm,
        ]);

        Log::info('GenerateEmbeddingJob: embedding generated', [
            'knowledge_id' => $this->knowledge->id,
            'embedding_model' => config('helpdeskagents.embeddings.model', 'text-embedding-3-small'),
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('GenerateEmbeddingJob: failed', [
            'knowledge_id' => $this->knowledge->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
