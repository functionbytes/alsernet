<?php

namespace Modules\HelpdeskHelpcenter\Observers;

use Illuminate\Support\Facades\Cache;
use Modules\HelpdeskHelpcenter\Http\Controllers\SitemapController;
use Modules\HelpdeskHelpcenter\Listeners\EmbedArticleOnSave;
use Modules\HelpdeskHelpcenter\Models\HelpCenterArticle;

class HelpCenterArticleObserver
{
    public function saved(HelpCenterArticle $article): void
    {
        app(EmbedArticleOnSave::class)->handle($article);
        Cache::forget(SitemapController::CACHE_KEY);
    }

    public function deleted(HelpCenterArticle $article): void
    {
        Cache::forget(SitemapController::CACHE_KEY);
    }
}
