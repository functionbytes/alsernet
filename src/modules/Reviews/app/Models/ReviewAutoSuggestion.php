<?php

namespace Modules\Reviews\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Reviews\Database\Factories\ReviewAutoSuggestionFactory;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ReviewAutoSuggestion extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'review_auto_suggestions';

    protected static function newFactory()
    {
        return ReviewAutoSuggestionFactory::new();
    }

    protected $fillable = [
        'trigger_keywords',
        'rating_range',
        'suggested_template_id',
        'priority',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'trigger_keywords' => 'array',
            'priority' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['trigger_keywords', 'rating_range', 'suggested_template_id', 'priority', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function suggestedTemplate(): BelongsTo
    {
        return $this->belongsTo(ReviewReplyTemplate::class, 'suggested_template_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByRatingRange($query, string $ratingRange)
    {
        return $query->where('rating_range', $ratingRange);
    }

    public function scopeOrderedByPriority($query)
    {
        return $query->orderByDesc('priority');
    }

    public function matchesReview(Review $review): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if (! $this->matchesRating($review->star_rating_value)) {
            return false;
        }

        if (empty($this->trigger_keywords)) {
            return true;
        }

        return $this->matchesKeywords($review->comment);
    }

    public function matchesRating(int $rating): bool
    {
        if (str_contains($this->rating_range, '-')) {
            [$min, $max] = explode('-', $this->rating_range);

            return $rating >= (int) $min && $rating <= (int) $max;
        }

        return $rating === (int) $this->rating_range;
    }

    public function matchesKeywords(?string $content): bool
    {
        if (empty($content)) {
            return false;
        }

        $content = mb_strtolower($content);

        foreach ($this->trigger_keywords as $keyword) {
            if (str_contains($content, mb_strtolower($keyword))) {
                return true;
            }
        }

        return false;
    }

    public function getRelevanceScore(Review $review): int
    {
        $score = $this->priority;

        if (! empty($this->trigger_keywords) && $this->matchesKeywords($review->comment)) {
            $score += 100;

            $content = mb_strtolower($review->comment ?? '');
            $matchCount = 0;

            foreach ($this->trigger_keywords as $keyword) {
                if (str_contains($content, mb_strtolower($keyword))) {
                    $matchCount++;
                }
            }

            $score += $matchCount * 10;
        }

        return $score;
    }
}
