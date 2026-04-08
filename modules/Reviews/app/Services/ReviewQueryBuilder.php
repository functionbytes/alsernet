<?php

namespace Modules\Reviews\Services;

use Illuminate\Database\Eloquent\Builder;
use Modules\Reviews\Models\Review;

class ReviewQueryBuilder
{
    public static function applyFilters(Builder $query, array $filters): Builder
    {
        if (isset($filters['location_id'])) {
            $query->where('location_id', $filters['location_id']);
        }

        if (isset($filters['rating']) || isset($filters['star_rating'])) {
            $query->where('star_rating', $filters['rating'] ?? $filters['star_rating']);
        }

        if (isset($filters['has_comment'])) {
            $filters['has_comment']
                ? $query->whereNotNull('comment')
                : $query->whereNull('comment');
        }

        if (isset($filters['has_reply'])) {
            $filters['has_reply']
                ? $query->whereNotNull('google_reply_text')
                : $query->whereNull('google_reply_text');
        }

        if (isset($filters['is_visible'])) {
            $query->whereHas('moderation', fn ($q) => $q->where('is_visible', $filters['is_visible']));
        }

        if (isset($filters['is_featured'])) {
            $query->whereHas('moderation', fn ($q) => $q->where('is_featured', $filters['is_featured']));
        }

        if (isset($filters['date_from'])) {
            $query->where('review_time', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('review_time', '<=', $filters['date_to']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('reviewer_name', 'like', "%{$search}%")
                    ->orWhere('comment', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    public static function make(array $filters = []): Builder
    {
        return static::applyFilters(Review::query(), $filters);
    }
}
