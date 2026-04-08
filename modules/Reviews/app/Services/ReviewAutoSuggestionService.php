<?php

namespace Modules\Reviews\Services;

use Illuminate\Support\Collection;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Models\ReviewAutoSuggestion;
use Modules\Reviews\Models\ReviewReplyTemplate;

class ReviewAutoSuggestionService
{
    public function __construct(
        private readonly LanguageDetectionService $languageDetection,
    ) {}

    public function suggestTemplates(Review $review): Collection
    {
        $rating = $review->star_rating_value;
        $detectedLanguage = $this->detectReviewerLanguage($review);

        $suggestions = ReviewAutoSuggestion::query()
            ->with('suggestedTemplate')
            ->active()
            ->get()
            ->filter(fn (ReviewAutoSuggestion $suggestion) => $suggestion->matchesReview($review));

        if ($suggestions->isEmpty()) {
            return $this->getFallbackTemplates($rating, $detectedLanguage);
        }

        $suggestions = $suggestions
            ->map(function (ReviewAutoSuggestion $suggestion) use ($review, $detectedLanguage) {
                $template = $suggestion->suggestedTemplate;
                $body = $template->getTextForLanguage($detectedLanguage);

                return [
                    'template_id' => $template->id,
                    'template_name' => $template->name,
                    'template_body' => $body,
                    'category' => $template->category,
                    'language' => $detectedLanguage,
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

    public function detectReviewerLanguage(Review $review): string
    {
        if (empty($review->comment)) {
            return 'es';
        }

        return $this->languageDetection->detect($review->comment);
    }

    protected function getFallbackTemplates(int $rating, string $language = 'es'): Collection
    {
        $category = match (true) {
            $rating >= 4 => 'positive',
            $rating <= 2 => 'negative',
            default => 'neutral',
        };

        // Prefer templates in the detected language; fall back to any language
        $templates = ReviewReplyTemplate::query()
            ->active()
            ->where(function ($query) use ($category) {
                $query->where('category', $category)
                    ->orWhere('category', 'general');
            })
            ->orderByRaw('CASE WHEN language = ? THEN 0 ELSE 1 END', [$language])
            ->orderByDesc('usage_count')
            ->take(5)
            ->get();

        return $templates->map(fn (ReviewReplyTemplate $template) => [
            'template_id' => $template->id,
            'template_name' => $template->name,
            'template_body' => $template->getTextForLanguage($language),
            'category' => $template->category,
            'language' => $language,
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
