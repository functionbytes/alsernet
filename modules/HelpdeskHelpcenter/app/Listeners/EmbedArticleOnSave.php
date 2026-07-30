<?php

namespace Modules\HelpdeskHelpcenter\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\HelpdeskHelpcenter\Jobs\RegenerateArticleEmbeddingsJob;
use Modules\HelpdeskHelpcenter\Models\HelpCenterArticle;

class EmbedArticleOnSave
{
    public function handle(HelpCenterArticle $article): void
    {
        if (! $article->is_published) {
            return;
        }

        $bodyChanged = $article->wasChanged('body')
            || $article->wasChanged('content')
            || $article->wasChanged('is_published');

        if (! $bodyChanged) {
            return;
        }

        $locale = app()->getLocale() ?: 'es';

        RegenerateArticleEmbeddingsJob::dispatch($article, $locale);

        Log::info('EmbedArticleOnSave: queued embedding regeneration', [
            'article_id' => $article->id,
            'locale' => $locale,
        ]);
    }
}
