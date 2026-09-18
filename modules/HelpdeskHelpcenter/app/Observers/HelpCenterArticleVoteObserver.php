<?php

namespace Modules\HelpdeskHelpcenter\Observers;

use Modules\HelpdeskHelpcenter\Models\HelpCenterArticle;
use Modules\HelpdeskHelpcenter\Models\HelpCenterArticleVote;

class HelpCenterArticleVoteObserver
{
    public function saved(HelpCenterArticleVote $vote): void
    {
        $this->syncCounts($vote->article_id);
    }

    public function deleted(HelpCenterArticleVote $vote): void
    {
        $this->syncCounts($vote->article_id);
    }

    private function syncCounts(int $articleId): void
    {
        $article = HelpCenterArticle::find($articleId);

        if (! $article) {
            return;
        }

        // forceFill bypasses $fillable so these aggregate columns (not in fillable) are saved.
        $article->forceFill([
            'helpful_count' => $article->votes()->where('vote', 1)->count(),
            'unhelpful_count' => $article->votes()->where('vote', -1)->count(),
        ])->saveQuietly();
    }
}
