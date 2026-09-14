<?php

namespace Modules\HelpdeskHelpcenter\Observers;

use Illuminate\Support\Facades\Cache;
use Modules\HelpdeskHelpcenter\Http\Controllers\SitemapController;
use Modules\HelpdeskHelpcenter\Listeners\EmbedArticleOnSave;
use Modules\HelpdeskHelpcenter\Models\HelpCenterArticle;
use Modules\HelpdeskHelpcenter\Services\HelpcenterWidgetService;

class HelpCenterArticleObserver
{
    public function saved(HelpCenterArticle $article): void
    {
        app(EmbedArticleOnSave::class)->handle($article);
        $this->forgetCaches();
    }

    public function deleted(HelpCenterArticle $article): void
    {
        $this->forgetCaches();
    }

    private function forgetCaches(): void
    {
        Cache::forget(SitemapController::CACHE_KEY);
        Cache::forget(HelpcenterWidgetService::WIDGET_CACHE_KEY);
    }
}
