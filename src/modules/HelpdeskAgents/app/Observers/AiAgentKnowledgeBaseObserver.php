<?php

namespace Modules\HelpdeskAgents\Observers;

use Modules\HelpdeskAgents\Jobs\GenerateEmbeddingJob;
use Modules\HelpdeskAgents\Models\AiAgentKnowledgeBase;

class AiAgentKnowledgeBaseObserver
{
    public function saved(AiAgentKnowledgeBase $kb): void
    {
        // Regenerate embedding whenever title or content changes
        if ($kb->wasChanged(['title', 'content']) || $kb->embedding === null) {
            GenerateEmbeddingJob::dispatch($kb);
        }
    }

    public function deleted(AiAgentKnowledgeBase $kb): void
    {
        // Nothing to clean up — embeddings are stored on the model itself
    }
}
