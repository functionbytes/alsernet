<?php

namespace Modules\Reviews\Imports;

use App\Imports\BaseImport;
use Carbon\Carbon;
use Modules\Reviews\Enums\ReviewRating;
use Modules\Reviews\Models\Review;

class ReviewImport extends BaseImport
{
    public function rules(): array
    {
        return [
            'reviewer_name' => 'required|string|max:255',
            'star_rating' => 'required|integer|between:1,5',
            'comment' => 'nullable|string|max:5000',
            'review_time' => 'nullable|date',
            'google_reply_text' => 'nullable|string|max:5000',
        ];
    }

    protected function processRow(array $row): void
    {
        Review::create([
            'reviewer_name' => $row['reviewer_name'],
            'star_rating' => ReviewRating::fromInt((int) $row['star_rating']),
            'comment' => $row['comment'] ?? null,
            'review_time' => $row['review_time'] ? Carbon::parse($row['review_time']) : now(),
            'google_reply_text' => $row['google_reply_text'] ?? null,
            'synced_at' => now(),
        ]);
    }
}
