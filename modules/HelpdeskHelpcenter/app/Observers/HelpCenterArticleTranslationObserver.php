<?php

namespace Modules\HelpdeskHelpcenter\Observers;

use Illuminate\Support\Facades\Cache;
use Modules\HelpdeskHelpcenter\Http\Controllers\SitemapController;
use Modules\HelpdeskHelpcenter\Jobs\RegenerateArticleEmbeddingsJob;
use Modules\HelpdeskHelpcenter\Models\HelpCenterArticleTranslation;

class HelpCenterArticleTranslationObserver
{
    public function saved(HelpCenterArticleTranslation $translation): void
    {
        Cache::forget(SitemapController::CACHE_KEY);

        if (! $translation->is_published) {
            return;
        }

        $bodyChanged = $translation->wasChanged('body')
            || $translation->wasChanged('title')
            || $translation->wasChanged('is_published');

        if (! $bodyChanged) {
            return;
        }

        $article = $translation->article;

        if (! $article) {
            return;
        }

        RegenerateArticleEmbeddingsJob::dispatch($article, $translation->locale);
    }
}
