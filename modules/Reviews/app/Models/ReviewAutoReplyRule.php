<?php

namespace Modules\Reviews\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewAutoReplyRule extends Model
{
    use HasFactory;

    protected $table = 'review_auto_reply_rules';

    protected $fillable = [
        'location_id',
        'name',
        'is_active',
        'conditions',
        'template_id',
        'custom_text',
        'delay_minutes',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'conditions' => 'array',
            'delay_minutes' => 'integer',
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(ReviewGoogleLocation::class, 'location_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ReviewReplyTemplate::class, 'template_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Determine whether this rule applies to the given review.
     *
     * Supported condition keys:
     *   min_rating (int 1-5)  – minimum star rating (inclusive)
     *   max_rating (int 1-5)  – maximum star rating (inclusive)
     *   has_comment (bool)    – whether the review must have / must not have a comment
     */
    public function matchesReview(Review $review): bool
    {
        $conditions = $this->conditions ?? [];
        $rating = $review->star_rating->value();

        if (isset($conditions['min_rating']) && $rating < (int) $conditions['min_rating']) {
            return false;
        }

        if (isset($conditions['max_rating']) && $rating > (int) $conditions['max_rating']) {
            return false;
        }

        if (isset($conditions['has_comment'])) {
            $hasComment = ! is_null($review->comment) && $review->comment !== '';
            if ((bool) $conditions['has_comment'] !== $hasComment) {
                return false;
            }
        }

        return true;
    }

    /**
     * Resolve the reply text for this rule, rendering against the given review if a template is set.
     */
    public function resolveReplyText(Review $review): string
    {
        if ($this->template_id && $this->relationLoaded('template') && $this->template) {
            return $this->template->renderForReview($review);
        }

        return $this->custom_text ?? '';
    }
}
