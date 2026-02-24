<?php

namespace Modules\Reviews\Services;

use Illuminate\Support\Collection;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Models\ReviewAutoSuggestion;
use Modules\Reviews\Models\ReviewReplyTemplate;

class ReviewAutoSuggestionService
{
    public function suggestTemplates(Review $review): Collection
    {
        $rating = $review->star_rating_value;

        $suggestions = ReviewAutoSuggestion::query()
            ->with('suggestedTemplate')
            ->active()
            ->get()
            ->filter(fn (ReviewAutoSuggestion $suggestion) => $suggestion->matchesReview($review));

        if ($suggestions->isEmpty()) {
            return $this->getFallbackTemplates($rating);
        }

        $suggestions = $suggestions
            ->map(function (ReviewAutoSuggestion $suggestion) use ($review) {
                $template = $suggestion->suggestedTemplate;

                return [
                    'template_id' => $template->id,
                    'template_name' => $template->name,
                    'template_body' => $template->body,
                    'category' => $template->category,
                    'relevance_score' => $suggestion->getRelevanceScore($review),
                    'matched_keywords' => $this->getMatchedKeywords($suggestion, $review),
                    'usage_count' => $template->usage_count,
                ];
            })
            ->unique('template_id')
            ->sortByDesc('relevance_score')
            ->values();

        return $suggestions;
    }

    protected function getFallbackTemplates(int $rating): Collection
    {
        $category = match (true) {
            $rating >= 4 => 'positive',
            $rating <= 2 => 'negative',
            default => 'neutral',
        };

        return ReviewReplyTemplate::query()
            ->active()
            ->where(function ($query) use ($category) {
                $query->where('category', $category)
                    ->orWhere('category', 'general');
            })
            ->orderByDesc('usage_count')
            ->take(5)
            ->get()
            ->map(fn (ReviewReplyTemplate $template) => [
                'template_id' => $template->id,
                'template_name' => $template->name,
                'template_body' => $template->body,
                'category' => $template->category,
                'relevance_score' => 0,
                'matched_keywords' => [],
                'usage_count' => $template->usage_count,
            ]);
    }

    protected function getMatchedKeywords(ReviewAutoSuggestion $suggestion, Review $review): array
    {
        if (empty($review->comment)) {
            return [];
        }

        $content = mb_strtolower($review->comment);
        $matched = [];

        foreach ($suggestion->trigger_keywords as $keyword) {
            if (str_contains($content, mb_strtolower($keyword))) {
                $matched[] = $keyword;
            }
        }

        return $matched;
    }

    public function createSuggestion(array $data): ReviewAutoSuggestion
    {
        return ReviewAutoSuggestion::create($data);
    }

    public function updateSuggestion(ReviewAutoSuggestion $suggestion, array $data): ReviewAutoSuggestion
    {
        $suggestion->update($data);

        return $suggestion->fresh();
    }

    public function deleteSuggestion(ReviewAutoSuggestion $suggestion): bool
    {
        return $suggestion->delete();
    }

    public function toggleActive(ReviewAutoSuggestion $suggestion): ReviewAutoSuggestion
    {
        $suggestion->update(['is_active' => ! $suggestion->is_active]);

        return $suggestion->fresh();
    }
}
