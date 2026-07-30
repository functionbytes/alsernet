<?php

namespace Modules\HelpdeskHelpcenter\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskHelpcenter\Models\HelpCenterArticle;
use Modules\HelpdeskHelpcenter\Services\EmbeddingsService;

class RegenerateArticleEmbeddingsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public int $backoff = 30;

    public function __construct(
        private readonly HelpCenterArticle $article,
        private readonly string $locale = 'es'
    ) {
        $this->onQueue('helpdesk-ai');
    }

    public function handle(EmbeddingsService $service): void
    {
        $chunks = $service->generateForArticle($this->article, $this->locale);

        Log::info('RegenerateArticleEmbeddingsJob: completed', [
            'article_id' => $this->article->id,
            'locale' => $this->locale,
            'chunks' => $chunks,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('RegenerateArticleEmbeddingsJob failed', [
            'article_id' => $this->article->id,
            'locale' => $this->locale,
            'error' => $exception->getMessage(),
        ]);
    }
}
